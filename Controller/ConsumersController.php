<?php
App::uses('LtiAppController', 'Lti.Controller');
App::import('Vendor', 'Lti.OAuth', ['file' => 'OAuth.php']);

class ConsumersController extends LtiAppController {
	public $components = ['DataTable'];
	public $scaffold = 'admin';
	public $displayField = 'name';
/**
 * Security
 *
 * @var array
 */
 	public $actions = [
 		'all' => [
 			'launch', 'response'
  		],
 		'admin' => [
 			'admin_index', 'admin_view', 'admin_add', 'admin_edit', 'admin_delete',
 		],
 		'ajax-only' => [
 		]
	];

	public function beforeFilter() {
		$this->Security->unlockedActions = ['admin_index', 'admin_add', 'admin_delete', 'admin_edit', 'launch', 'response'];
		parent::beforeFilter();
	}


	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}


	public function launch() {
		$launch_url = Router::url(['plugin' => 'lti', 'admin'=>false, 'controller' => 'providers', 'action' => 'request'], true);
		$return_url = Router::url(['plugin' => 'lti', 'admin'=>false, 'controller' => 'consumers', 'action' => 'response'], true);
		$outcome_url = Router::url(['plugin' => 'lti', 'admin'=>false, 'controller' => 'consumers', 'action' => 'outcome'], true);
		$default_lmsdata = [
			"resource_link_id" => "120988f929-274612",
			"resource_link_title" => "Weekly Blog",
			"resource_link_description" => "A weekly blog.",
			"user_id" => "292832126",
			"roles" => "Instructor",  // or Learner
			"lis_person_name_full" => 'Jane Q. Public',
			"lis_person_name_family" => 'Public',
			"lis_person_name_given" => 'Jane',
			"lis_person_contact_email_primary" => "user@school.edu",
			"lis_person_sourcedid" => "school.edu:user",
			"context_id" => "456434513",
			"context_title" => "Design of Personal Environments",
			"context_label" => "SI182",
			"tool_consumer_info_product_family_code" => "ims",
			"tool_consumer_info_version" => "1.1",
			"tool_consumer_instance_guid" => "lmsng.school.edu",
			"tool_consumer_instance_description" => "University of School (LMSng)",
			"launch_presentation_locale" => "en-US",
			"launch_presentation_document_target" => "frame",
			"launch_presentation_width" => null,
			"launch_presentation_height" => null,
			"launch_presentation_css_url"=> "http://www.imsglobal.org/developers/LTI/test/v1p1/lms.css",
		];

		$default_desc = str_replace("CUR_URL", $launch_url,
'<?xml version="1.0" encoding="UTF-8"?>
<basic_lti_link xmlns="http://www.imsglobal.org/services/cc/imsblti_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <title>A Simple Descriptor</title>
    <custom>
        <parameter key="Cool:Factor">120</parameter>
    </custom>
    <launch_url>CUR_URL</launch_url>
</basic_lti_link>
');

		$default_vars = [
			'key' => "12345",
			'secret' => 'secret',
			'endpoint' => $launch_url,
			'urlformat' => false,
			'format' => '',
			'xmldesc' => $default_desc,
		];

	    foreach ($default_lmsdata as $k => $val ) {
	    	$lmsdata[$k] = $val;
	        if (!empty($this->request->data['Consumer'][$k]) ) {
	            $lmsdata[$k] = $this->request->data['Consumer'][$k];
	        }
	    }

		foreach ($default_vars as $k => $v) {
			$$k = $v;
	        if ( !empty($this->request->data['Consumer'][$k]) ) {
	            $$k = $this->request->data['Consumer'][$k];
	            $default_vars[$k] = $$k;
	        }
		}

	    $urlformat = ( $format != 'XML' );

	    $xmldesc = str_replace("\\\"","\"",$xmldesc);

	  	$this->set(compact('lmsdata', 'key', 'secret', 'urlformat', 'endpoint', 'xmldesc'));

	  	$params = [];
		if ( $urlformat ) {
			$params = $lmsdata;
		} else {
			$cx = $this->_launchInfo($xmldesc);
			$endpoint = $cx["launch_url"];

			if ( empty($endpoint) ) {
				echo("<p>Error, did not find a launch_url or secure_launch_url in the XML descriptor</p>\n");
				exit();
			}
			$custom = $cx["custom"];
			$params = array_merge($lmsdata, $custom);
		}

		// Cleanup parms before we sign
		foreach( $params as $k => $val ) {
			if (strlen(trim($params[$k]) ) < 1 ) {
				 unset($params[$k]);
			}
		}

		// Add oauth_callback to be compliant with the 1.0A spec
		$params["oauth_callback"] = "about:blank";
	    $params["lti_version"] = "LTI-1p0";
	    $params["lti_message_type"] = "basic-lti-launch-request";
	    $params["launch_presentation_return_url"] = $return_url;
	    $params["lis_outcome_service_url"] = $outcome_url;
		$params["lis_result_sourcedid"] = "feb-123-456-2929::28883";
		$params = $this->_signLaunchParameters($endpoint, "POST", $params, $key, $secret);
	    // ksort($params);
	    // pr($params);
		$params["ext_submit"] = "Press to Launch"; // this is so we can inject launch buttons with custom text
		$this->set("iframeattr", "width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency");
		$this->set("params", $params);
		$this->request->data['Consumer'] = am($lmsdata, $default_vars, $params);
	}

	public function response() {
		$this->layout = 'basic';
		foreach (['lti_msg', 'lti_errormsg', 'lti_log', 'lti_errorlog'] as $var) {
			$$var = '';
			if (!empty($this->request->query[$var])) {
				$$var = $this->request->query[$var];
			}
			$this->set($var, $$var);
		}
	}

	  // Parse a descriptor
	protected function _launchInfo($xmldata) {
	    $xml = new SimpleXMLElement($xmldata);
	    if ( empty($xml) ) {
	       echo("Error parsing Descriptor XML\n");
	       return;
	    }
	    $launch_url = $xml->secure_launch_url[0];

	    if ( empty($launch_url) ) {
	    	$launch_url = $xml->launch_url[0];
	    }

    	$launch_url = (string) $launch_url;

	    $custom = array();
	    if ( !empty($xml->custom[0]->parameter ) ) {
		    foreach ( $xml->custom[0]->parameter as $resource) {
		      $key = (string) $resource['key'];
		      $key = strtolower($key);
		      $nk = "";
		      for($i=0; $i < strlen($key); $i++) {
		        $ch = substr($key,$i,1);
		        if ( $ch >= "a" && $ch <= "z" ) $nk .= $ch;
		        else if ( $ch >= "0" && $ch <= "9" ) $nk .= $ch;
		        else $nk .= "_";
		      }
		      $value = (string) $resource;
		      $custom["custom_".$nk] = $value;
		    }
		}
	    return [ "launch_url" => $launch_url, "custom" => $custom ] ;
	}


/**
 * Is the consumer key available to accept launch requests?
 *
 * @return boolean True if the consumer key is enabled and within any date constraints
 */
	public function getIsAvailable() {

		$ok = $this->Consumer->enabled;

		$now = time();
		if ($ok && !is_null($this->Consumer->enable_from)) {
			$ok = $this->Consumer->enable_from <= $now;
		}
		if ($ok && !is_null($this->Consumer->enable_until)) {
			$ok = $this->Consumer->enable_until > $now;
		}

		return $ok;

	}

/**
 * Add the OAuth signature to an LTI message.
 *
 * @param string  $url         URL for message request
 * @param string  $type        LTI message type
 * @param string  $version     LTI version
 * @param array   $params      Message parameters
 *
 * @return array Array of signed message parameters
 */
	protected function _signParameters($url, $type, $version, $params) {

		if (!empty($url)) {
// Check for query parameters which need to be included in the signature
			$query_params = array();
			$query_string = parse_url($url, PHP_URL_QUERY);
			if (!is_null($query_string)) {
				$query_items = explode('&', $query_string);
				foreach ($query_items as $item) {
					if (strpos($item, '=') !== FALSE) {
						list($name, $value) = explode('=', $item);
						$query_params[urldecode($name)] = urldecode($value);
					} else {
						$query_params[urldecode($item)] = '';
					}
				}
			}
			$params = $params + $query_params;
// Add standard parameters
			$params['lti_version'] = $version;
			$params['lti_message_type'] = $type;
			$params['oauth_callback'] = 'about:blank';
// Add OAuth signature
			$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
			$consumer = new OAuthConsumer($this->getKey(), $this->secret, NULL);
			$req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'POST', $url, $params);
			$req->sign_request($hmac_method, $consumer, NULL);
			$params = $req->get_parameters();
// Remove parameters being passed on the query string
			foreach (array_keys($query_params) as $name) {
				unset($params[$name]);
			}
		}

		return $params;

	}

	protected function _signLaunchParameters($endpoint, $method="POST", $params, $oauth_consumer_key, $oauth_consumer_secret)
	{

	    $token = null;

	    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	    $consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

	    $acc_req = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $endpoint, $params);
	    $acc_req->sign_request($hmac_method, $consumer, $token);

	    // Pass this back up "out of band" for debugging
	    $last_base_string = $acc_req->get_signature_base_string();
	    $this->set('last_base_string', $last_base_string);
	    $params = $acc_req->get_parameters();

	    return $params;
	}

###
###  PRIVATE METHOD
###

/**
 * Load the tool consumer from the database.
 *
 * @param string  $key        The consumer key value
 * @param boolean $autoEnable True if the consumer should be enabled (optional, default if false)
 *
 * @return boolean True if the consumer was successfully loaded
 */
	private function load($key, $autoEnable = FALSE) {

		$this->Consumer->initialise();
		$this->Consumer->key = $key;
		$ok = $this->Consumer->load();
		if (!$ok) {
			$this->Consumer->enabled = $autoEnable;
		}

		return $ok;

	}




	public function admin_index() {
		$this->helpers[] = 'DataTable';
		if ($this->request->is('ajax')) {
			$this->DataTable->emptyElements = 1;

			$this->Paginator->settings = [
				'contain' => [],
				'fields' => ['Consumer.id', 'Consumer.name', 'Consumer.consumer_key', 'Consumer.consumer_guid'],
				'order' => [ 'Consumer.name' => 'asc' ]
			];
			$consumers = $this->DataTable->getResponse();
			$this->set('consumers', $consumers);
			$this->set('_serialize','consumers');
		}

	}



}
