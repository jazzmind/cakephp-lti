<?php
App::uses('LtiAppController', 'Lti.Controller');
App::import('Vendor', 'Lti.OAuth', ['file' => 'OAuth.php']);

class ResourceLinksController extends LtiAppController {
	public $components = [];
	public $scaffold = 'admin';





/**
 * Check if the Outcomes service is supported.
 *
 * @return boolean True if this resource link supports the Outcomes service (either the LTI 1.1 or extension service)
 */
	public function hasOutcomesService() {

		$url = $this->getSetting('ext_ims_lis_basic_outcome_url') . $this->getSetting('lis_outcome_service_url');

		return !empty($url);

	}

/**
 * Check if the Memberships service is supported.
 *
 * @return boolean True if this resource link supports the Memberships service
 */
	public function hasMembershipsService() {

		$url = $this->getSetting('ext_ims_lis_memberships_url');

		return !empty($url);

	}

/**
 * Check if the Setting service is supported.
 *
 * @return boolean True if this resource link supports the Setting service
 */
	public function hasSettingService() {

		$url = $this->getSetting('ext_ims_lti_tool_setting_url');

		return !empty($url);

	}

/**
 * Perform an Outcomes service request.
 *
 * @param int $action The action type constant
 * @param LTI_Outcome $lti_outcome Outcome object
 * @param LTI_User $user User object
 *
 * @return boolean True if the request was successfully processed
 */
	public function doOutcomesService($action, $lti_outcome, $user = NULL) {

		$response = FALSE;
		$this->ext_response = NULL;
#
### Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
#
		$source_resource_link = $this;
		$sourcedid = $lti_outcome->getSourcedid();
		if (!is_null($user)) {
			$source_resource_link = $user->getResourceLink();
			$sourcedid = $user->lti_result_sourcedid;
		}
#
### Use LTI 1.1 service in preference to extension service if it is available
#
		$urlLTI11 = $source_resource_link->getSetting('lis_outcome_service_url');
		$urlExt = $source_resource_link->getSetting('ext_ims_lis_basic_outcome_url');
		if ($urlExt || $urlLTI11) {
			switch ($action) {
				case self::EXT_READ:
					if ($urlLTI11 && ($lti_outcome->type == self::EXT_TYPE_DECIMAL)) {
						$do = 'readResult';
					} else if ($urlExt) {
						$urlLTI11 = NULL;
						$do = 'basic-lis-readresult';
					}
					break;
				case self::EXT_WRITE:
					if ($urlLTI11 && $this->checkValueType($lti_outcome, array(self::EXT_TYPE_DECIMAL))) {
						$do = 'replaceResult';
					} else if ($this->checkValueType($lti_outcome)) {
						$urlLTI11 = NULL;
						$do = 'basic-lis-updateresult';
					}
					break;
				case self::EXT_DELETE:
					if ($urlLTI11 && ($lti_outcome->type == self::EXT_TYPE_DECIMAL)) {
						$do = 'deleteResult';
					} else if ($urlExt) {
						$urlLTI11 = NULL;
						$do = 'basic-lis-deleteresult';
					}
					break;
			}
		}
		if (isset($do)) {
			$value = $lti_outcome->getValue();
			if (is_null($value)) {
				$value = '';
			}
			if ($urlLTI11) {
				$xml = '';
				if ($action == self::EXT_WRITE) {
					$xml = <<<EOF

				<result>
					<resultScore>
						<language>{$lti_outcome->language}</language>
						<textString>{$value}</textString>
					</resultScore>
				</result>
EOF;
				}
				$sourcedid = htmlentities($sourcedid);
				$xml = <<<EOF
			<resultRecord>
				<sourcedGUID>
					<sourcedId>{$sourcedid}</sourcedId>
				</sourcedGUID>{$xml}
			</resultRecord>
EOF;
				if ($this->doLTI11Service($do, $urlLTI11, $xml)) {
					switch ($action) {
						case self::EXT_READ:
							if (!isset($this->ext_nodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString'])) {
								break;
							} else {
								$lti_outcome->setValue($this->ext_nodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString']);
							}
						case self::EXT_WRITE:
						case self::EXT_DELETE:
							$response = TRUE;
							break;
					}
				}
			} else {
				$params = array();
				$params['sourcedid'] = $sourcedid;
				$params['result_resultscore_textstring'] = $value;
				if (!empty($lti_outcome->language)) {
					$params['result_resultscore_language'] = $lti_outcome->language;
				}
				if (!empty($lti_outcome->status)) {
					$params['result_statusofresult'] = $lti_outcome->status;
				}
				if (!empty($lti_outcome->date)) {
					$params['result_date'] = $lti_outcome->date;
				}
				if (!empty($lti_outcome->type)) {
					$params['result_resultvaluesourcedid'] = $lti_outcome->type;
				}
				if (!empty($lti_outcome->data_source)) {
					$params['result_datasource'] = $lti_outcome->data_source;
				}
				if ($this->doService($do, $urlExt, $params)) {
					switch ($action) {
						case self::EXT_READ:
							if (isset($this->ext_nodes['result']['resultscore']['textstring'])) {
								$response = $this->ext_nodes['result']['resultscore']['textstring'];
							}
							break;
						case self::EXT_WRITE:
						case self::EXT_DELETE:
							$response = TRUE;
							break;
					}
				}
			}
			if (is_array($response) && (count($response) <= 0)) {
				$response = '';
			}
		}

		return $response;

	}

/**
 * Perform a Memberships service request.
 *
 * The user table is updated with the new list of user objects.
 *
 * @param boolean $withGroups True is group information is to be requested as well
 *
 * @return mixed Array of LTI_User objects or False if the request was not successful
 */
	public function doMembershipsService($withGroups = FALSE) {
		$users = array();
		$old_users = $this->getUserResultSourcedIDs(TRUE, LTI_Tool_Provider::ID_SCOPE_RESOURCE);
		$this->ext_response = NULL;
		$url = $this->getSetting('ext_ims_lis_memberships_url');
		$params = array();
		$params['id'] = $this->getSetting('ext_ims_lis_memberships_id');
		$ok = FALSE;
		if ($withGroups) {
			$ok = $this->doService('basic-lis-readmembershipsforcontextwithgroups', $url, $params);
		}
		if ($ok) {
			$this->group_sets = array();
			$this->groups = array();
		} else {
			$ok = $this->doService('basic-lis-readmembershipsforcontext', $url, $params);
		}

		if ($ok) {
			if (!isset($this->ext_nodes['memberships']['member'])) {
				$members = array();
			} else if (!isset($this->ext_nodes['memberships']['member'][0])) {
				$members = array();
				$members[0] = $this->ext_nodes['memberships']['member'];
			} else {
				$members = $this->ext_nodes['memberships']['member'];
			}

			for ($i = 0; $i < count($members); $i++) {

				$user = new LTI_User($this, $members[$i]['user_id']);
#
### Set the user name
#
				$firstname = (isset($members[$i]['person_name_given'])) ? $members[$i]['person_name_given'] : '';
				$lastname = (isset($members[$i]['person_name_family'])) ? $members[$i]['person_name_family'] : '';
				$fullname = (isset($members[$i]['person_name_full'])) ? $members[$i]['person_name_full'] : '';
				$user->setNames($firstname, $lastname, $fullname);
#
### Set the user email
#
				$email = (isset($members[$i]['person_contact_email_primary'])) ? $members[$i]['person_contact_email_primary'] : '';
				$user->setEmail($email, $this->consumer->defaultEmail);
#
### Set the user roles
#
				if (isset($members[$i]['roles'])) {
					$user->roles = LTI_Tool_Provider::parseRoles($members[$i]['roles']);
				}
#
### Set the user groups
#
				if (!isset($members[$i]['groups']['group'])) {
					$groups = array();
				} else if (!isset($members[$i]['groups']['group'][0])) {
					$groups = array();
					$groups[0] = $members[$i]['groups']['group'];
				} else {
					$groups = $members[$i]['groups']['group'];
				}
				for ($j = 0; $j < count($groups); $j++) {
					$group = $groups[$j];
					if (isset($group['set'])) {
						$set_id = $group['set']['id'];
						if (!isset($this->group_sets[$set_id])) {
							$this->group_sets[$set_id] = array('title' => $group['set']['title'], 'groups' => array(),
								 'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
						}
						$this->group_sets[$set_id]['num_members']++;
						if ($user->isStaff()) {
							$this->group_sets[$set_id]['num_staff']++;
						}
						if ($user->isLearner()) {
							$this->group_sets[$set_id]['num_learners']++;
						}
						if (!in_array($group['id'], $this->group_sets[$set_id]['groups'])) {
							$this->group_sets[$set_id]['groups'][] = $group['id'];
						}
						$this->groups[$group['id']] = array('title' => $group['title'], 'set' => $set_id);
					} else {
						$this->groups[$group['id']] = array('title' => $group['title']);
					}
					$user->groups[] = $group['id'];
				}
#
### If a result sourcedid is provided save the user
#
				if (isset($members[$i]['lis_result_sourcedid'])) {
					$user->lti_result_sourcedid = $members[$i]['lis_result_sourcedid'];
					$user->save();
				}
				$users[] = $user;
#
### Remove old user (if it exists)
#
				unset($old_users[$user->getId(LTI_Tool_Provider::ID_SCOPE_RESOURCE)]);
			}
#
### Delete any old users which were not in the latest list from the tool consumer
#
			foreach ($old_users as $id => $user) {
				$user->delete();
			}
		} else {
			$users = FALSE;
		}

		return $users;

	}

/**
 * Perform a Setting service request.
 *
 * @param int    $action The action type constant
 * @param string $value  The setting value (optional, default is null)
 *
 * @return mixed The setting value for a read action, true if a write or delete action was successful, otherwise false
 */
	public function doSettingService($action, $value = NULL) {

		$response = FALSE;
		$this->ext_response = NULL;
		switch ($action) {
			case self::EXT_READ:
				$do = 'basic-lti-loadsetting';
				break;
			case self::EXT_WRITE:
				$do = 'basic-lti-savesetting';
				break;
			case self::EXT_DELETE:
				$do = 'basic-lti-deletesetting';
				break;
		}
		if (isset($do)) {

			$url = $this->getSetting('ext_ims_lti_tool_setting_url');
			$params = array();
			$params['id'] = $this->getSetting('ext_ims_lti_tool_setting_id');
			if (is_null($value)) {
				$value = '';
			}
			$params['setting'] = $value;

			if ($this->doService($do, $url, $params)) {
				switch ($action) {
					case self::EXT_READ:
						if (isset($this->ext_nodes['setting']['value'])) {
							$response = $this->ext_nodes['setting']['value'];
							if (is_array($response)) {
								$response = '';
							}
						}
						break;
					case self::EXT_WRITE:
						$this->setSetting('ext_ims_lti_tool_setting', $value);
						$this->saveSettings();
						$response = TRUE;
						break;
					case self::EXT_DELETE:
						$response = TRUE;
						break;
				}
			}

		}

		return $response;

	}

/**
 * Obtain an array of LTI_User objects for users with a result sourcedId.
 *
 * The array may include users from other resource links which are sharing this resource link.
 * It may also be optionally indexed by the user ID of a specified scope.
 *
 * @param boolean $local_only True if only users from this resource link are to be returned, not users from shared resource links (optional, default is false)
 * @param int     $id_scope     Scope to use for ID values (optional, default is null for consumer default)
 *
 * @return array Array of LTI_User objects
 */
	public function getUserResultSourcedIDs($local_only = FALSE, $id_scope = NULL) {

		return $this->consumer->getDataConnector()->Resource_Link_getUserResultSourcedIDs($this, $local_only, $id_scope);

	}

/**
 * Get an array of LTI_Resource_Link_Share objects for each resource link which is sharing this context.
 *
 * @return array Array of LTI_Resource_Link_Share objects
 */
	public function getShares() {

		return $this->consumer->getDataConnector()->Resource_Link_getShares($this);

	}

###
###  PRIVATE METHODS
###

/**
 * Load the resource link from the database.
 *
 * @return boolean True if resource link was successfully loaded
 */
	private function load() {

		$this->initialise();
		return $this->consumer->getDataConnector()->Resource_Link_load($this);

	}

/**
 * Convert data type of value to a supported type if possible.
 *
 * @param LTI_Outcome $lti_outcome     Outcome object
 * @param string[]    $supported_types Array of outcome types to be supported (optional, default is null to use supported types reported in the last launch for this resource link)
 *
 * @return boolean True if the type/value are valid and supported
 */
	private function checkValueType($lti_outcome, $supported_types = NULL) {

		if (empty($supported_types)) {
			$supported_types = explode(',', str_replace(' ', '', strtolower($this->getSetting('ext_ims_lis_resultvalue_sourcedids', self::EXT_TYPE_DECIMAL))));
		}
		$type = $lti_outcome->type;
		$value = $lti_outcome->getValue();
// Check whether the type is supported or there is no value
		$ok = in_array($type, $supported_types) || (strlen($value) <= 0);
		if (!$ok) {
// Convert numeric values to decimal
			if ($type == self::EXT_TYPE_PERCENTAGE) {
				if (substr($value, -1) == '%') {
					$value = substr($value, 0, -1);
				}
				$ok = is_numeric($value) && ($value >= 0) && ($value <= 100);
				if ($ok) {
					$lti_outcome->setValue($value / 100);
					$lti_outcome->type = self::EXT_TYPE_DECIMAL;
				}
			} else if ($type == self::EXT_TYPE_RATIO) {
				$parts = explode('/', $value, 2);
				$ok = (count($parts) == 2) && is_numeric($parts[0]) && is_numeric($parts[1]) && ($parts[0] >= 0) && ($parts[1] > 0);
				if ($ok) {
					$lti_outcome->setValue($parts[0] / $parts[1]);
					$lti_outcome->type = self::EXT_TYPE_DECIMAL;
				}
// Convert letter_af to letter_af_plus or text
			} else if ($type == self::EXT_TYPE_LETTER_AF) {
				if (in_array(self::EXT_TYPE_LETTER_AF_PLUS, $supported_types)) {
					$ok = TRUE;
					$lti_outcome->type = self::EXT_TYPE_LETTER_AF_PLUS;
				} else if (in_array(self::EXT_TYPE_TEXT, $supported_types)) {
					$ok = TRUE;
					$lti_outcome->type = self::EXT_TYPE_TEXT;
				}
// Convert letter_af_plus to letter_af or text
			} else if ($type == self::EXT_TYPE_LETTER_AF_PLUS) {
				if (in_array(self::EXT_TYPE_LETTER_AF, $supported_types) && (strlen($value) == 1)) {
					$ok = TRUE;
					$lti_outcome->type = self::EXT_TYPE_LETTER_AF;
				} else if (in_array(self::EXT_TYPE_TEXT, $supported_types)) {
					$ok = TRUE;
					$lti_outcome->type = self::EXT_TYPE_TEXT;
				}
// Convert text to decimal
			} else if ($type == self::EXT_TYPE_TEXT) {
				$ok = is_numeric($value) && ($value >= 0) && ($value <=1);
				if ($ok) {
					$lti_outcome->type = self::EXT_TYPE_DECIMAL;
				} else if (substr($value, -1) == '%') {
					$value = substr($value, 0, -1);
					$ok = is_numeric($value) && ($value >= 0) && ($value <=100);
					if ($ok) {
						if (in_array(self::EXT_TYPE_PERCENTAGE, $supported_types)) {
							$lti_outcome->type = self::EXT_TYPE_PERCENTAGE;
						} else {
							$lti_outcome->setValue($value / 100);
							$lti_outcome->type = self::EXT_TYPE_DECIMAL;
						}
					}
				}
			}
		}

		return $ok;

	}

/**
 * Send a service request to the tool consumer.
 *
 * @param string $type   Message type value
 * @param string $url    URL to send request to
 * @param array  $params Associative array of parameter values to be passed
 *
 * @return boolean True if the request successfully obtained a response
 */
	private function doService($type, $url, $params) {

		$ok = FALSE;
		$this->ext_request = NULL;
		$this->ext_request_headers = '';
		$this->ext_response = NULL;
		$this->ext_response_headers = '';
		if (!empty($url)) {
			$params = $this->consumer->signParameters($url, $type, $this->consumer->lti_version, $params);
// Connect to tool consumer
			$http = new LTI_HTTP_Message($url, 'POST', $params);
// Parse XML response
			if ($http->send()) {
				$this->ext_response = $http->response;
				$this->ext_response_headers = $http->response_headers;
				try {
					$this->ext_doc = new DOMDocument();
					$this->ext_doc->loadXML($http->response);
					$this->ext_nodes = $this->domnode_to_array($this->ext_doc->documentElement);
					if (isset($this->ext_nodes['statusinfo']['codemajor']) && ($this->ext_nodes['statusinfo']['codemajor'] == 'Success')) {
						$ok = TRUE;
					}
				} catch (Exception $e) {
				}
			}
			$this->ext_request = $http->request;
			$this->ext_request_headers = $http->request_headers;
		}

		return $ok;

	}

/**
 * Send a service request to the tool consumer.
 *
 * @param string $type Message type value
 * @param string $url  URL to send request to
 * @param string $xml  XML of message request
 *
 * @return boolean True if the request successfully obtained a response
 */
	private function doLTI11Service($type, $url, $xml) {

		$ok = FALSE;
		$this->ext_request = NULL;
		$this->ext_request_headers = '';
		$this->ext_response = NULL;
		$this->ext_response_headers = '';
		if (!empty($url)) {
			$id = uniqid();
			$xmlRequest = <<< EOD
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
	<imsx_POXHeader>
		<imsx_POXRequestHeaderInfo>
			<imsx_version>V1.0</imsx_version>
			<imsx_messageIdentifier>{$id}</imsx_messageIdentifier>
		</imsx_POXRequestHeaderInfo>
	</imsx_POXHeader>
	<imsx_POXBody>
		<{$type}Request>
{$xml}
		</{$type}Request>
	</imsx_POXBody>
</imsx_POXEnvelopeRequest>
EOD;
// Calculate body hash
			$hash = base64_encode(sha1($xmlRequest, TRUE));
			$params = array('oauth_body_hash' => $hash);

// Add OAuth signature
			$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
			$consumer = new OAuthConsumer($this->consumer->getKey(), $this->consumer->secret, NULL);
			$req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'POST', $url, $params);
			$req->sign_request($hmac_method, $consumer, NULL);
			$params = $req->get_parameters();
			$header = $req->to_header();
			$header .= "\nContent-Type: application/xml";
// Connect to tool consumer
			$http = new LTI_HTTP_Message($url, 'POST', $xmlRequest, $header);
// Parse XML response
			if ($http->send()) {
				$this->ext_response = $http->response;
				$this->ext_response_headers = $http->response_headers;
				try {
					$this->ext_doc = new DOMDocument();
					$this->ext_doc->loadXML($http->response);
					$this->ext_nodes = $this->domnode_to_array($this->ext_doc->documentElement);
					if (isset($this->ext_nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor']) &&
							($this->ext_nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor'] == 'success')) {
						$ok = TRUE;
					}
				} catch (Exception $e) {
				}
			}
			$this->ext_request = $http->request;
			$this->ext_request_headers = $http->request_headers;
		}

		return $ok;

	}

/**
 * Convert DOM nodes to array.
 *
 * @param DOMElement $node XML element
 *
 * @return array Array of XML document elements
 */
	private function domnode_to_array($node) {

		$output = '';
		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;
			case XML_ELEMENT_NODE:
				for ($i = 0; $i < $node->childNodes->length; $i++) {
					$child = $node->childNodes->item($i);
					$v = $this->domnode_to_array($child);
					if (isset($child->tagName)) {
						$t = $child->tagName;
						if (!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					} else {
						$s = (string) $v;
						if (strlen($s) > 0) {
							$output = $s;
						}
					}
				}
				if (is_array($output)) {
					if ($node->attributes->length) {
						$a = array();
						foreach ($node->attributes as $attrName => $attrNode) {
							$a[$attrName] = (string) $attrNode->value;
						}
						$output['@attributes'] = $a;
					}
					foreach ($output as $t => $v) {
						if (is_array($v) && count($v)==1 && $t!='@attributes') {
							$output[$t] = $v[0];
						}
					}
				}
				break;
		}

		return $output;

	}

}
