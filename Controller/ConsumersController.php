<?php
App::uses('LTIAppController', 'LTI.Controller');
App::uses('OAuth', 'LTI.Lib');

class ProvidersController extends LTIAppController {
	public $components = [];

/**
 * Security
 *
 * @var array
 */
 	public $actions = [
 		'all' => [
  		],
 		'admin' => [
 			'admin_index'/*, 'admin_add', 'admin_edit', 'admin_delete', 'admin_award', 'admin_conditions', 'admin_condition_remove' */
 		],
 		'ajax-only' => [
 		]
	];

	public function beforeFilter() {
		$this->Security->unlockedActions = ['admin_index'];
		parent::beforeFilter();
	}


	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}

	public function admin_index() {
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
	public function signParameters($url, $type, $version, $params) {

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

}
