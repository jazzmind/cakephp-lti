<?php
App::uses('Component', 'Controller');
App::uses('Provider', 'Lti.Model');

class LtiRequestComponent extends Component {

	public function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->Provider = $this->controller->Provider;
	}

	public function validate($cohort=null) {
		$now = time();
		$data = $this->controller->request->data;
		$this->cohort = $cohort;
		$this->Provider->isOK = false;

		// Check consumer key

		if (empty($data['oauth_consumer_key'])) {
			return $this->Provider->reason = 'Missing consumer key.';
		}

		// Check all required launch parameters
		if (empty($data['lti_message_type']) or !array_key_exists($data['lti_message_type'], $this->Provider->messageTypes)) {
			return $this->Provider->reason = 'Invalid or missing lti_message_type parameter.';
		}

		if (empty($data['lti_version']) or !in_array($data['lti_version'], $this->Provider->LTI_VERSIONS)) {
			return $this->Provider->reason = 'Invalid or missing lti_version parameter.';
		}

		switch ($data['lti_message_type']) {
			case 'APITokenRequest':
				$this->Provider->isJsonOutput = true;
				break;
			case 'ContentItemSelectionRequest':
					if (empty($data['content_item_return_url']) or !(strlen(trim($data['content_item_return_url'])) > 0)) {
					return $this->Provider->reason = 'Missing content_item_return_url parameter.';
				}

				if (!empty($data['accept_media_types']) and (strlen(trim($data['accept_media_types'])) > 0)) {
					$mediaTypes = array_filter(explode(',', str_replace(' ', '', $data['accept_media_types'])), 'strlen');
					$mediaTypes = array_unique($mediaTypes);
				}
				if (empty($mediaTypes)) {
					return $this->Provider->reason = 'No accept_media_types found.';
				}
				$this->Provider->mediaTypes = $mediaTypes;

				if (!empty($data['accept_presentation_document_targets']) and (strlen(trim($data['accept_presentation_document_targets'])) > 0)) {
					$documentTargets = array_filter(explode(',', str_replace(' ', '', $data['accept_presentation_document_targets'])), 'strlen');
					$documentTargets = array_unique($documentTargets);
				}
				if (empty($documentTargets)) {
					return $this->Provider->reason = 'Missing or empty accept_presentation_document_targets parameter.';
				}

				foreach ($documentTargets as $documentTarget) {
					if (!in_array($documentTarget, ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay', 'none'])) {
						return $this->Provider->reason = 'Invalid value in accept_presentation_document_targets parameter: ' . $documentTarget;
					}
				}
				$this->Provider->documentTargets = $documentTargets;

				if (!empty($data['accept_unsigned']) and !in_array($data['accept_unsigned'], ['true', 'false'])) {
					return $this->Provider->reason = 'Invalid value for accept_unsigned parameter: ' . $data['accept_unsigned'];
				}
				if (!empty($data['accept_multiple']) and !in_array($data['accept_multiple'], ['true', 'false'])) {
					return $this->Provider->reason = 'Invalid value for accept_multiple parameter: ' . $data['accept_multiple'];
				}
				if (!empty($data['accept_copy_advice']) and !in_array($data['accept_copy_advice'], ['true', 'false'])) {
					return $this->Provider->reason = 'Invalid value for accept_copy_advice parameter: ' . $data['accept_copy_advice'];
				}
				if (!empty($data['auto_create']) and !in_array($data['auto_create'], ['true', 'false'])) {
					return $this->Provider->reason = 'Invalid value for auto_create parameter: ' . $data['auto_create'];
				}
				if (!empty($data['can_confirm']) and !in_array($data['can_confirm'], ['true', 'false'])) {
					return $this->Provider->reason = 'Invalid value for can_confirm parameter: ' . $data['can_confirm'];
				}

				break;

			case 'basic-lti-launch-request':
			case 'DashboardRequest':
				if (empty($data['resource_link_id']) or !(strlen(trim($data['resource_link_id'])) > 0)) {
					return $this->Provider->reason = 'Missing resource link ID.';
				}
				// fall through
			default:
				if (!empty($data['launch_presentation_document_target']) and !in_array($data['launch_presentation_document_target'], ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay'])) {
					return $this->Provider->reason = 'Invalid value for launch_presentation_document_target parameter: ' . $data['launch_presentation_document_target'];
				}
				break;
		}
		#
		### Get the consumer
		#
		$this->Consumer = ClassRegistry::init('Lti.Consumer');

		$this->Consumer->contain();
		$result = $this->Consumer->findByConsumerKey($data['oauth_consumer_key']);
		if (empty($result)) {
			return $this->Provider->reason = 'Invalid consumer key.';
		}
		$this->Consumer->data = $result['Consumer'];
		foreach ($result['Consumer'] as $k => $v) {
			$this->Consumer->$k = $v;
		}

		if ($this->Consumer->protect === true) {
			if (empty($data['tool_consumer_instance_guid'])) {
				return $this->Provider->reason = 'A tool consumer GUID must be included in the launch request.';
			}
			if (empty($data['tool_consumer_instance_guid'])) {
				return $this->Provider->reason = 'Request is from an invalid tool consumer: ' . $this->Consumer->consumer_guid;
			}
			if (empty($this->Consumer->consumer_guid) or ($this->Consumer->consumer_guid != $data['tool_consumer_instance_guid'])) {
				return $this->Provider->reason = 'Tool consumer ID in request is different from what is in Practera and protect mode is on, preventing us from updating the value automatically: ' . $this->Consumer->consumer_guid;
			}
		}

		if (!$this->Consumer->enabled) {
				return $this->Provider->reason = 'Tool consumer has not been enabled by the tool provider.';
		}

		if (!empty($this->Consumer->enable_from) and (CakeTime::fromString($this->Consumer->enable_from) > $now)) {
			return $this->Provider->reason = 'Tool consumer access is not yet available. It will be available from ' . $this->Consumer->enable_from;
		}

		if (!empty($this->Consumer->enable_until) and (CakeTime::fromString($this->Consumer->enable_until) <= $now)) {
			return $this->Provider->reason = 'Tool consumer access expired on ' . $this->Consumer->enable_until;
		}


		#
		### Validate message parameter constraints
		#
		if (!empty($this->Provider->constraints)) {
			$invalid_parameters = array();
			foreach ($this->Provider->constraints as $name => $constraint) {
				if (empty($constraint['messages']) || in_array($data['lti_message_type'], $constraint['messages'])) {
					if ($constraint['required']) {
						if (empty($data[$name]) or (strlen(trim($data[$name])) <= 0)) {
							$invalid_parameters[] = "{$name} (missing)";
							continue;
						}
					}
					if (!empty($constraint['max_length'])) {
						if (strlen(trim($data[$name])) > $constraint['max_length']) {
							$invalid_parameters[] = "{$name} (too long)";
						}
					}
				}
			}

			if (count($invalid_parameters) > 0) {
				return $this->Provider->reason = 'Invalid parameter(s): ' . implode(', ', $invalid_parameters) . '.';
			}
		}

		$this->Provider->isOK = true;
		return true;
	}
/**
 * Check the authenticity of the LTI launch request.
 *
 * The consumer, resource link and user objects will be initialised if the request is valid.
 *
 * @return boolean True if the request has been successfully validated.
 */
	public function authenticate() {
		if (!$this->Provider->isOK) {
			return false;
		}
		try {
			$this->OAuthStore = ClassRegistry::init('Lti.OAuthStore');
			$store = new OAuthStore($this->Provider, $this->Consumer);
			$server = new OAuthServer($store);
			$method = new OAuthSignatureMethod_HMAC_SHA1();
			$server->add_signature_method($method);
			$request = OAuthRequest::from_request($this->controller->request->method(), Router::url(null, true));
			$res = $server->verify_request($request);
		} catch (Exception $e) {
			$this->Provider->isOK = FALSE;
			if (empty($this->Provider->reason)) {
				if ($this->Provider->debugMode) {
					$oconsumer = new OAuthConsumer($this->Consumer->consumer_key, $this->Consumer->secret);
					$signature = $request->build_signature($method, $oconsumer, FALSE);
					$this->Provider->reason = $e->getMessage();
					if (empty($this->Provider->reason)) {
						$this->Provider->reason = 'OAuth exception';
					}
					$this->Provider->details[] = 'Timestamp: ' . time();
					$this->Provider->details[] = "Signature: {$signature}";
					$this->Provider->details[] = "Base string: {$request->base_string}]";
				} else {
					$this->Provider->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
				}
			}
			return false;
		}

		return true;

	}


	public function initSalesforce($consumerKey) {
		if (!$this->Provider->isOK) {
			return false;
		}
		$this->OAuthStore = ClassRegistry::init('Lti.OAuthStore');
		list($sig, $base) = explode('.', $this->controller->request->data['signed_request']);
		$data = json_decode(base64_decode($base),true);	

		$this->controller->request->data["tool_consumer_instance_guid"] = $data['context']['application']['applicationId'];		
		$this->controller->request->data["tool_consumer_instance_description"] = $data['context']['application']['name'];		
		$this->controller->request->data["context_id"] = substr($data['context']['environment']['locationUrl'], 0, 254);		
		$this->controller->request->data["context_title"] = substr($data['context']['environment']['locationUrl'], 0, 254);
		$this->controller->request->data["lis_person_sourcedid"] = $data['context']['user']['userId'];
		$this->controller->request->data["lis_person_name_full"] = $data['context']['user']['fullName'];
		$this->controller->request->data["lis_person_name_family"] = $data['context']['user']['lastName'];
		$this->controller->request->data["lis_person_name_given"] = $data['context']['user']['firstName'];
		$this->controller->request->data["lis_person_contact_email_primary"] = $data['context']['user']['email'];
		$this->controller->request->data["resource_link_id"] = $consumerKey;
		$this->controller->request->data["lti_message_type"] = "basic-lti-launch-request";
		$this->controller->request->data["roles"] = 'Learner';
		$this->controller->request->data["lti_version"] = 'LTI-1p0';
		$this->controller->request->data["oauth_signature_method"] = 'HMAC-SHA256';
		$this->controller->request->data["oauth_version"] = '1.0';		
		$this->controller->request->data["oauth_timestamp"] = $data['issuedAt'];		

	}

	public function authenticateSalesforce() {
		if (!$this->Provider->isOK) {
			return false;
		}

		try {
			$this->OAuthStore = ClassRegistry::init('Lti.OAuthStore');
			list($sig, $base) = explode('.', $this->controller->request->data['signed_request']);
			$secret = $this->Consumer->secret;
			$built = base64_encode(hash_hmac('sha256', $base, $secret, true));
			if ($built !== $sig) {
				throw new Exception("invalid signature $sig");
			}
		} catch (Exception $e) {
			$this->Provider->isOK = FALSE;
			if (empty($this->Provider->reason)) {
				if ($this->Provider->debugMode) {
					$this->Provider->reason = $e->getMessage();
					if (empty($this->Provider->reason)) {
						$this->Provider->reason = 'Could not verify signature';
					}
					$this->Provider->details[] = 'Timestamp: ' . time();
					$this->Provider->details[] = "Signature: {$sig}";
					$this->Provider->details[] = "Payload: {$base}]";
				} else {
					$this->Provider->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
				}
			}
			return false;
		}
		return true;		
	}

	// This function creates a ResourceLink entry which connects a resource_link_id with a consumer_key
	// If an entry exists, it uses that one
	public function setResourceLink() {
		$this->ResourceLink = ClassRegistry::init('Lti.ResourceLink');
		$data = $this->controller->request->data;
		if (empty($data['resource_link_id'])) {
			return;
		}

		if (empty($this->Consumer->consumer_key)) {
			return;
		}
		#
		### Set the request context/resource link
		#
		#
		// there might be a user defined content item ID passed in
		$content_item_id = '';
		if (!empty($data['custom_content_item_id'])) {
			$content_item_id = $data['custom_content_item_id'];
		}

		// first try loading the resource link for the specific context_id item
		$this->ResourceLink->lti_resource_id = trim($data['resource_link_id']);
		$conditions =  [
				'consumer_key' => $this->Consumer->consumer_key,
				'lti_resource_id' => $this->ResourceLink->lti_resource_id
		];
		$this->ResourceLink->data = $this->ResourceLink->find('first', ['conditions' => $conditions]);

		// however if we can't find this, load for the content_item
		if (empty($this->ResourceLink->data) and !empty($content_item_id)) {
			$conditions['lti_resource_id'] = $this->ResourceLink->lti_resource_id = $content_item_id;
			$this->ResourceLink->data = $this->ResourceLink->find('first', ['conditions' => $conditions]);
		}

		if (empty($this->ResourceLink->data)) {
			$this->ResourceLink->consumer_key = $this->ResourceLink->data['consumer_key'] = $conditions['consumer_key'];
			$this->ResourceLink->lti_resource_id = $conditions['lti_resource_id'];
		}

		if (!empty($data['resource_link_id'])) {
			$this->ResourceLink->lti_resource_id = trim($data['resource_link_id']);
		}

		if (!empty($data['context_id'])) {
			$this->ResourceLink->lti_context_id = trim($data['context_id']);
		} else {
			// if we didn't get a context, use the resource link id as the context
			$this->ResourceLink->lti_context_id = trim($data['resource_link_id']);
		}
		$this->ResourceLink->data['lti_context_id'] = $this->ResourceLink->lti_context_id;
		$this->ResourceLink->data['lti_resource_id'] = $this->ResourceLink->lti_resource_id;

		$title = '';
		if (!empty($data['context_title'])) {
			$title = trim($data['context_title']);
		}
		if (!empty($data['resource_link_title']) && (strlen(trim($data['resource_link_title'])) > 0)) {
			if (!empty($title)) {
				$title .= ': ';
			}
			$title .= trim($data['resource_link_title']);
		}
		if (empty($title)) {
			$title = "Course Item {$this->ResourceLink->resource_link_id}";
		}
		$this->ResourceLink->data['title'] = $this->ResourceLink->title = $title;


		// Save LTI parameters
		foreach ($this->Provider->lti_settings_names as $name) {
			if (!empty($data[$name])) {
				$this->ResourceLink->setSetting($name, $data[$name]);
			} else {
				$this->ResourceLink->setSetting($name, NULL);
			}
		}

		// Delete any existing custom parameters
		$settings = $this->ResourceLink->getSettings();
		if (!empty($settings)) {
			foreach ($settings as $name => $value) {
				if (strpos($name, 'custom_') === 0) {
					$this->ResourceLink->setSetting($name);
				}
			}
		}
		// Save custom parameters
		foreach ($data as $name => $value) {
			if (strpos($name, 'custom_') === 0) {
				$this->ResourceLink->setSetting($name, $value);
			}
		}

		#
		### Check if a share arrangement is in place for this resource link
		#
		//$this->Provider->isOK = $this->_checkForShare();

		#
		### Persist changes to resource link
		#

		$this->ResourceLink->save($this->ResourceLink->data);

	}

	public function setUser() {
		$this->LtiUser = ClassRegistry::init('Lti.LtiUser');
		$this->LtiUser->contain();
		if (empty($this->ResourceLink)) {
			return;
		}
		$data = $this->controller->request->data;
		$email = (isset($data['lis_person_contact_email_primary'])) ? $data['lis_person_contact_email_primary'] : '';
		$user_id =  (isset($data['user_id'])) ? $data['user_id'] : '';
		// cannot match a user without the lis_person_sourcedid or email
		if (empty($data['lis_person_sourcedid']) && empty($user_id) && empty($email)) {
			return;
		}
		
		// if there is no lis_person_sourcedid but an email we will use that
		if (empty($data['lis_person_sourcedid'])) {
			$data['lis_person_sourcedid'] = $email;
			if (!empty($user_id)) {
				$data['lis_person_sourcedid'] = $user_id;
			}
		}

		$conditions = [
			'consumer_key' => $this->Consumer->consumer_key,
			'context_id' => $this->ResourceLink->lti_context_id,
			'lis_person_sourcedid' => [ trim($data['lis_person_sourcedid']), trim($email) ]
		];
	
		$result = $this->LtiUser->find('first', ['contain' => [], 'conditions' => $conditions]);
		
		#
		### Set the user instance
		#
		if (empty($result)) {
			$this->LtiUser->user_id = $this->LtiUser->data['LtiUser']['user_id'] = null;
			$this->LtiUser->lis_person_sourcedid = $this->LtiUser->data['LtiUser']['lis_person_sourcedid'] = trim($data['lis_person_sourcedid']);
			$this->LtiUser->consumer_key = $this->LtiUser->data['LtiUser']['consumer_key'] = $conditions['consumer_key'];
			$this->LtiUser->context_id = $this->LtiUser->data['LtiUser']['context_id'] = $conditions['context_id'];
			
		} else {
			$this->LtiUser->data = $result;
			// we're going to keep going, even though we found a matching user
			// this is so we can update our own records with any user changes that have happened
			// through the tool consumer, e.g. name changes
		}
		#
		### Set the user name
		#
		$firstname = (isset($data['lis_person_name_given'])) ? $data['lis_person_name_given'] : '';
		$lastname = (isset($data['lis_person_name_family'])) ? $data['lis_person_name_family'] : '';
		$fullname = (isset($data['lis_person_name_full'])) ? $data['lis_person_name_full'] : '';
		$this->LtiUser->setNames($firstname, $lastname, $fullname);

		#
		### Set the user email
		#
		$this->LtiUser->setEmail($email, $this->defaultEmail);

		#
		### Set the user roles
		#
		if (isset($data['roles'])) {
			$this->LtiUser->data['roles'] = $this->LtiUser->roles = $this->Provider->parseRoles($data['roles']);
		}

		#
		### Save the user instance, or delete it if we weren't passed an LIS source ID
		#
	
		if (!empty($data['lis_result_sourcedid'])) {
			if (empty($this->LtiUser->data['LtiUser']['lis_result_sourcedid']) || $this->LtiUser->data['LtiUser']['lis_result_sourcedid'] != $data['lis_result_sourcedid']) {
				$this->LtiUser->data['LtiUser']['lis_result_sourcedid'] = $data['lis_result_sourcedid'];
			}
		}

		// if our result didn't have an lis_person_sourcedid use email
		if (empty($this->LtiUser->data['LtiUser']['lis_person_sourcedid'])) {
			$this->LtiUser->data['LtiUser']['lis_person_sourcedid'] = $data['lis_person_sourcedid'];
		}
		if (!empty($this->LtiUser->data['LtiUser']['id'])) {
			$this->LtiUser->id = $this->LtiUser->data['LtiUser']['id'];
		}
		$this->LtiUser->save($this->LtiUser->data['LtiUser']);
		$this->LtiUser->data = $this->LtiUser->find('first', ['conditions' => $conditions]);


		// not sure why we want to delete if we didn't get the sourceid - we still have an entry
		// if (empty($this->LtiUser->lis_result_sourcedid)) {
		// 	$this->LtiUser->delete();
		// }

	}

	public function setConsumer() {
		#
		### Initialise the consumer and check for changes
		#
		$doSaveConsumer = FALSE;
		$data = $this->controller->request->data;

		$now = time();
		$today = date('Y-m-d', $now);
		if (empty($this->Consumer->data['last_access'])) {
			$doSaveConsumer = TRUE;
		} else {
			$last = $this->Consumer->data['last_access'];
			$doSaveConsumer = $doSaveConsumer || ($last != $today);
		}
		$this->Consumer->data['last_access'] = $today;
		$this->Consumer->data['defaultEmail'] = $this->Provider->defaultEmail;
		if ($this->Consumer->data['lti_version'] != $data['lti_version']) {
			$this->Consumer->data['lti_version'] = $data['lti_version'];
			$doSaveConsumer = TRUE;
		}
		if (isset($data['tool_consumer_instance_name'])) {
			if ($this->Consumer->data['consumer_name'] != $data['tool_consumer_instance_name']) {
				$this->Consumer->data['consumer_name'] = $data['tool_consumer_instance_name'];
				$doSaveConsumer = TRUE;
			}
		}
		if (isset($data['tool_consumer_info_product_family_code'])) {
			$version = $data['tool_consumer_info_product_family_code'];
			if (isset($data['tool_consumer_info_version'])) {
				$version .= "-{$data['tool_consumer_info_version']}";
			}

			// do not delete any existing consumer version if none is passed
			if ($this->Consumer->data['consumer_version'] != $version) {
				$this->Consumer->data['consumer_version'] = $version;
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_lms']) && ($this->Consumer->data['consumer_name'] != $data['ext_lms'])) {
			$this->Consumer->data['consumer_version'] = $data['ext_lms'];
			$doSaveConsumer = TRUE;
		}

		if (isset($data['tool_consumer_instance_guid'])) {
			if (empty($this->Consumer->data['consumer_guid'])) {
				$this->Consumer->data['consumer_guid'] = $data['tool_consumer_instance_guid'];
				$doSaveConsumer = TRUE;
			} else if (!$this->Consumer->data['protect']) {
				$doSaveConsumer = ($this->Consumer->data['consumer_guid'] != $data['tool_consumer_instance_guid']);
				if ($doSaveConsumer) {
					$this->Consumer->data['consumer_guid'] = $data['tool_consumer_instance_guid'];
				}
			}
		}

		if (isset($data['launch_presentation_css_url'])) {
			if ($this->Consumer->data['css_path'] != $data['launch_presentation_css_url']) {
				$this->Consumer->data['css_path'] = $data['launch_presentation_css_url'];
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_launch_presentation_css_url']) &&
			 ($this->Consumer->data['css_path'] != $data['ext_launch_presentation_css_url'])) {
			$this->Consumer->data['css_path'] = $data['ext_launch_presentation_css_url'];
			$doSaveConsumer = TRUE;
		} else if (!empty($this->Consumer->data['css_path'])) {
			$this->Consumer->data['css_path'] = NULL;
			$doSaveConsumer = TRUE;
		}

		#
		### Persist changes to consumer
		#
		if ($doSaveConsumer && $this->Consumer->id) {
			$this->Consumer->save($this->Consumer->data);
		}
		$this->Consumer->read();
	}

/**
 * Call any callback function for the requested action.
 *
 * This function may set the redirectURL and output properties.
 *
 * @param string  $type             Callback type
 *
 * @return boolean True if no error reported
 */
	public function doCallbackMethod($type = NULL) {

		$callback = $type;
		if (is_null($callback)) {
			$callback = $this->Provider->messageTypes[$this->controller->request->data['lti_message_type']];
		}
		if (empty($this->Provider->callbackHandler[$callback])) {
			if ($callback == 'error') {
				return false;
			}
			$this->Provider->isOK = FALSE;
			$this->Provider->reason = 'Message type not supported.';
			return false;
		}
		$handler = $this->Provider->callbackHandler[$callback];
		if (is_array($handler)) {
			if (empty($handler['model'])) {
				$this->Provider->isOK = FALSE;
				return $this->Provider->reason = 'Callback handler not configured.';
			}
			$this->{$handler['model']} = ClassRegistry::init($handler['model']);
			if (!method_exists($this->{$handler['model']}, $handler['method'])) {
				$this->Provider->isOK = FALSE;
				return $this->Provider->reason = 'Callback handler not configured: no method.';
			}
			$result = $this->{$handler['model']}->{$handler['method']}($this);
		} else {
			$result = call_user_func($handler, $this);
		}

		#
		### Callback function may return HTML, JSON, a redirect URL, or a boolean value
		#
		if (is_string($result)) {
			if ((substr($result, 0, 7) == 'http://') || (substr($result, 0, 8) == 'https://')) {
				$this->Provider->redirectURL = $result;
			} else {
				if (is_null($this->Provider->output)) {
					$this->Provider->output = '';
				}
				$this->Provider->output .= $result;
			}
		} else if (is_bool($result)) {
			$this->Provider->isOK = $result;
		} else {
			$this->Provider->output = $result;
		}
	}


/**
 * Check if a share arrangement is in place.
 *
 * @return boolean True if no error is reported
 */
	public function checkForShare() {

		$doSaveResourceLink = TRUE;
		$data = $this->controller->request->data;
		$key = $this->ResourceLink->primary_consumer_key;
		$id = $this->ResourceLink->primary_resource_link_id;

		$shareRequest = isset($data['custom_share_key']) && !empty($data['custom_share_key']);
		if ($shareRequest) {
			if (!$this->allowSharing) {
				$this->Provider->reason = 'Your sharing request has been refused because sharing is not being permitted.';
				return false;
			}
// Check if this is a new share key
			$this->ShareKey = ClassRegistry::init('Lti.ShareKey');
			//$share_key = new Share_Key($this->ResourceLink, $data['custom_share_key']);
			if (!is_null($this->ShareKey->primary_consumer_key) && !is_null($this->ShareKey->primary_resource_link_id)) {
	// Update resource link with sharing primary resource link details
				$key = $this->ShareKey->primary_consumer_key;
				$id = $this->ShareKey->primary_resource_link_id;
				$ok = ($key != $this->Consumer->consumer_key) || ($id != $this->ResourceLink->context_id);
				if ($ok) {
					$this->ResourceLink->primary_consumer_key = $key;
					$this->ResourceLink->primary_resource_link_id = $id;
					$this->ResourceLink->share_approved = $this->ShareKey->auto_approve;
					$ok = $this->ResourceLink->save();
					if ($ok) {
						$doSaveResourceLink = FALSE;
						$this->LtiUser->getResourceLink()->primary_consumer_key = $key;
						$this->LtiUser->getResourceLink()->primary_resource_link_id = $id;
						$this->LtiUser->getResourceLink()->share_approved = $this->SheareKey->auto_approve;
						$this->LtiUser->getResourceLink()->modified = time();
// Remove share key
						$this->ShareKey->delete();
					} else {
						$this->Provider->reason = 'An error occurred initialising your share arrangement.';
					}
				} else {
					$this->Provider->reason = 'It is not possible to share your resource link with yourself.';
				}
			}
			if ($ok) {
				$ok = !is_null($key);
				if (!$ok) {
					$this->Provider->reason = 'You have requested to share a resource link but none is available.';
				} else {
					$ok = (!is_null($this->LtiUser->getResourceLink()->share_approved) && $this->LtiUser->getResourceLink()->share_approved);
					if (!$ok) {
						$this->Provider->reason = 'Your share request is waiting to be approved.';
					}
				}
			}
		} else {
// Check no share is in place
			$ok = is_null($key);
			if (!$ok) {
				$this->Provider->reason = 'You have not requested to share a resource link but an arrangement is currently in place.';
			}
		}

// Look up primary resource link
		if ($ok && !is_null($key)) {
			$consumer = new LTI_Tool_Consumer($key, $this->data_connector);
			$ok = !is_null($consumer->created);
			if ($ok) {
				$resource_link = new LTI_Resource_Link($consumer, $id);
				$ok = !is_null($resource_link->created);
			}
			if ($ok) {
				if ($doSaveResourceLink) {
					$this->ResourceLink->save();
				}
			} else {
				$this->Provider->reason = 'Unable to load resource link being shared.';
			}
		}

		return $ok;

	}

}
