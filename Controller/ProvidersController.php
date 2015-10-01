<?php
App::uses('LtiAppController', 'Lti.Controller');

class ProvidersController extends LtiAppController {
	public $components = [];
	public $scaffold = 'admin';
/**
 * Security
 *
 * @var array
 */
 	public $actions = [
 		'all' => [
 			'request'
  		],
 		'admin' => [
 			'admin_index', 'admin_add', 'admin_edit', 'admin_delete',
 		],
 		'ajax-only' => [
 		]
	];

	public function beforeFilter() {
		$this->Security->unlockedActions = ['admin_index', 'admin_add', 'admin_edit', 'admin_delete', 'request'];
		$this->Auth->allow('request');

		parent::beforeFilter();
	}


	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}

	public function request() {
		$this->layout = 'basic';
		#
		### Perform action
		#
		$this->_init();

		$this->_validate();

		if ($this->_authenticate()) {

			$this->_setResourceLink();

			$this->_setUser();

			$this->_setConsumer();

			$this->_doCallbackMethod();

		}

		$this->_result();

	}


###
###  INTERNAL METHODS
###

	protected function _init() {
		$this->Provider->callbackHandler = array();
		if (!empty(Configure::read('LTI.callbackHandler'))) {
			$callbackHandler = Configure::read('LTI.callbackHandler');
			if (is_array($callbackHandler)) {
				if (!empty($callbackHandler['connect']) and empty($callbackHandler['launch'])) {  // for backward compatibility
					$callbackHandler['launch'] = $callbackHandler['connect'];
					unset($callbackHandler['connect']);
				}
				$this->Provider->callbackHandler = $callbackHandler;
			} else if (!empty($callbackHandler)) {
				$this->Provider->callbackHandler['launch'] = $callbackHandler;
			}
		}
		#
		### Set debug mode
		#
		if (!empty($this->request->data['custom_debug'])) {
			$this->Provider->debugMode = true;
		}

		### Set return URL if available
		#
		if (isset($this->request->data['launch_presentation_return_url'])) {
			$this->Provider->return_url = $this->request->data['launch_presentation_return_url'];
		} else if (isset($this->request->data['content_item_return_url'])) {
			$this->Provider->return_url = $this->request->data['content_item_return_url'];
		}


	}

	protected function _validate() {
		$now = time();
		$data = $this->request->data;
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
		$this->loadModel('Lti.Consumer');
		$this->Consumer->id = $data['oauth_consumer_key'];
		$this->Consumer->read();
		if (empty($this->Consumer->consumer_key)) {
			return $this->Provider->reason = 'Invalid consumer key.';
		}

		if ($this->Consumer->protect) {
			if (empty($data['tool_consumer_instance_guid'])) {
				return $this->Provider->reason = 'A tool consumer GUID must be included in the launch request.';
			}
			if (empty($this->Consumer->consumer_guid) or !($this->Consumer->consumer_guid == $data['tool_consumer_instance_guid'])) {
				return $this->Provider->reason = 'Request is from an invalid tool consumer.';
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
	}
/**
 * Check the authenticity of the LTI launch request.
 *
 * The consumer, resource link and user objects will be initialised if the request is valid.
 *
 * @return boolean True if the request has been successfully validated.
 */
	protected function _authenticate() {
		if (!$this->Provider->isOK) {
			return false;
		}
		try {
			$this->loadModel('Lti.OAuthStore');
			$store = new OAuthStore($this->Provider, $this->Consumer);
			$server = new OAuthServer($this->OAuthStore);
			$method = new OAuthSignatureMethod_HMAC_SHA1();
			$server->add_signature_method($method);
			$request = OAuthRequest::from_request();
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

	protected function _setResourceLink() {
		$data = $this->request->data;
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
		$this->loadModel('Lti.ResourceLink');
		$this->ResourceLink->context_id = trim($data['resource_link_id']);
		$conditions =  [
				'consumer_key' => $this->Consumer->consumer_key,
				'context_id' => $this->ResourceLink->context_id
		];
		$this->ResourceLink->data = $this->ResourceLink->find('first', ['conditions' => $conditions]);

		// however if we can't find this, load for the content_item
		if (empty($this->ResourceLink->data) and !empty($content_item_id)) {
			$conditions['context_id'] = $this->ResourceLink->context_id = $content_item_id;
			$this->ResourceLink->data = $this->ResourceLink->find('first', ['conditions' => $conditions]);
		}
		if (empty($this->ResourceLink->data)) {
			$this->ResourceLink->consumer_key = $conditions['consumer_key'];
			$this->ResourceLink->context_id = $conditions['context_id'];
		}

		if (!empty($data['context_id'])) {
			$this->ResourceLink->lti_context_id = trim($data['context_id']);
		}

		$this->ResourceLink->lti_resource_id = trim($data['resource_link_id']);
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
			$title = "Course {$this->ResourceLink->context_id}";
		}
		$this->ResourceLink->title = $title;

		// Save LTI parameters
		foreach ($this->Provider->lti_settings_names as $name) {
			if (!empty($data[$name])) {
				$this->ResourceLink->setSetting($name, $data[$name]);
			} else {
				$this->ResourceLink->setSetting($name, NULL);
			}
		}

		// Delete any existing custom parameters
		foreach ($this->ResourceLink->getSettings() as $name => $value) {
			if (strpos($name, 'custom_') === 0) {
				$this->ResourceLink->setSetting($name);
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
		$this->ResourceLink->save();
	}

	protected function _setUser() {
		if (empty($this->ResourceLink)) {
			return;
		}
 		$data = $this->request->data;
		#
		### Set the user instance
		#
		$user_id = '';
		if (isset($data['user_id'])) {
			$user_id = trim($data['user_id']);
		}

		$conditions = [
			'consumer_key' => $this->Consumer->consumer_key,
			'context_id' => $this->ResourceLink->context_id,
			'user_id' => $user_id
		];

		$this->Consumer->LTIUser->data = $this->Consumer->LTIUser->find('first', ['conditions' => $conditions]);
		if (empty($this->Consumer->LTIUser->data)) {
			$this->Consumer->LTIUser->consumer_key = $conditions['consumer_key'];
			$this->Consumer->LTIUser->context_id = $conditions['context_id'];
			$this->Consumer->LTIUser->user_id = $conditions['user_id'];
		} else {
			$this->Consumer->LTIUser->id = $this->Consumer->LTIUser->data['LTIUser']['id'];
		}
		#
		### Set the user name
		#
		$firstname = (isset($data['lis_person_name_given'])) ? $data['lis_person_name_given'] : '';
		$lastname = (isset($data['lis_person_name_family'])) ? $data['lis_person_name_family'] : '';
		$fullname = (isset($data['lis_person_name_full'])) ? $data['lis_person_name_full'] : '';
		$this->Consumer->LTIUser->setNames($firstname, $lastname, $fullname);

		#
		### Set the user email
		#
		$email = (isset($data['lis_person_contact_email_primary'])) ? $data['lis_person_contact_email_primary'] : '';
		$this->Consumer->LTIUser->setEmail($email, $this->defaultEmail);

		#
		### Set the user roles
		#
		if (isset($data['roles'])) {
			$this->Consumer->LTIUser->roles = $this->Provider->parseRoles($data['roles']);
		}

		#
		### Save the user instance, or delete it if we weren't passed an LIS source ID
		#
		if (isset($data['lis_result_sourcedid'])) {
			if ($this->Consumer->LTIUser->lti_result_sourcedid != $data['lis_result_sourcedid']) {
				$this->Consumer->LTIUser->lti_result_sourcedid = $data['lis_result_sourcedid'];
				$this->Consumer->LTIUser->save();
				$this->Consumer->LTIUser->data = $this->Consumer->LTIUser->find('first', ['conditions' => $conditions]);
			}
		} else if (!empty($this->Consumer->LTIUser->lti_result_sourcedid)) {
			$this->Consumer->LTIUser->delete();
		}

	}

	protected function _setConsumer() {
		#
		### Initialise the consumer and check for changes
		#
		$doSaveConsumer = FALSE;
		$data = $this->request->data;
		$now = time();
		$today = date('Y-m-d', $now);
		if (empty($this->Consumer->last_access)) {
			$doSaveConsumer = TRUE;
		} else {
			$last = $this->Consumer->last_access;
			$doSaveConsumer = $doSaveConsumer || ($last != $today);
		}
		$this->Consumer->last_access = $today;
		$this->Consumer->defaultEmail = $this->Provider->defaultEmail;
		if ($this->Consumer->lti_version != $data['lti_version']) {
			$this->Consumer->lti_version = $data['lti_version'];
			$doSaveConsumer = TRUE;
		}
		if (isset($data['tool_consumer_instance_name'])) {
			if ($this->Consumer->consumer_name != $data['tool_consumer_instance_name']) {
				$this->Consumer->consumer_name = $data['tool_consumer_instance_name'];
				$doSaveConsumer = TRUE;
			}
		}
		if (isset($data['tool_consumer_info_product_family_code'])) {
			$version = $data['tool_consumer_info_product_family_code'];
			if (isset($data['tool_consumer_info_version'])) {
				$version .= "-{$data['tool_consumer_info_version']}";
			}

			// do not delete any existing consumer version if none is passed
			if ($this->Consumer->consumer_version != $version) {
				$this->Consumer->consumer_version = $version;
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_lms']) && ($this->Consumer->consumer_name != $data['ext_lms'])) {
			$this->Consumer->consumer_version = $data['ext_lms'];
			$doSaveConsumer = TRUE;
		}

		if (isset($data['tool_consumer_instance_guid'])) {
			if (is_null($this->Consumer->consumer_guid)) {
				$this->Consumer->consumer_guid = $data['tool_consumer_instance_guid'];
				$doSaveConsumer = TRUE;
			} else if (!$this->Consumer->protect) {
				$doSaveConsumer = ($this->Consumer->consumer_guid != $data['tool_consumer_instance_guid']);
				if ($doSaveConsumer) {
					$this->Consumer->consumer_guid = $data['tool_consumer_instance_guid'];
				}
			}
		}

		if (isset($data['launch_presentation_css_url'])) {
			if ($this->Consumer->css_path != $data['launch_presentation_css_url']) {
				$this->Consumer->css_path = $data['launch_presentation_css_url'];
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_launch_presentation_css_url']) &&
			 ($this->Consumer->css_path != $data['ext_launch_presentation_css_url'])) {
			$this->Consumer->css_path = $data['ext_launch_presentation_css_url'];
			$doSaveConsumer = TRUE;
		} else if (!empty($this->Consumer->css_path)) {
			$this->Consumer->css_path = NULL;
			$doSaveConsumer = TRUE;
		}

		#
		### Persist changes to consumer
		#
		if ($doSaveConsumer) {
			$this->Consumer->save();
		}

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
	protected function _doCallbackMethod($type = NULL) {

		$callback = $type;
		if (is_null($callback)) {
			$callback = $this->Provider->messageTypes[$this->request->data['lti_message_type']];
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
			$this->loadModel($handler['model']);
			if (!method_exists($this->{$handler['model']}, $handler['method'])) {
				$this->Provider->isOK = FALSE;
				return $this->Provider->reason = 'Callback handler not configured: no method.';
			}
			$result = $this->{$handler['model']}->{$handler['method']}($this);
		} else {
			$result = call_user_func($handler, $this);
		}

#
### Callback function may return HTML, a redirect URL, or a boolean value
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
		}
	}

/**
 * Perform the result of an action.
 *
 * This function may redirect the user to another URL rather than returning a value.
 *
 * @return string Output to be displayed (redirection, or display HTML or message)
 */
	protected function _result() {

		$ok = FALSE;
		if (!$this->Provider->isOK) {
			$ok = $this->_doCallbackMethod('error');
		}
		if (!$ok) {
			if (!$this->Provider->isOK) {
#
### If not valid, return an error message to the tool consumer if a return URL is provided
#
				if (!empty($this->Provider->return_url)) {
					$error_url = $this->Provider->return_url;
					if (strpos($error_url, '?') === FALSE) {
						$error_url .= '?';
					} else {
						$error_url .= '&';
					}
					if ($this->Provider->debugMode && !empty($this->Provider->reason)) {
						$error_url .= 'lti_errormsg=' . urlencode("Debug error: {$this->Provider->reason}");
					} else {
						$error_url .= 'lti_errormsg=' . urlencode($this->Provider->message);
						if (!empty($this->Provider->reason)) {
							$error_url .= '&lti_errorlog=' . urlencode("Debug error: {$this->Provider->reason}");
						}
					}
					if (!empty($this->Consumer) and !empty($this->request->data['lti_message_type']) && ($this->request->data['lti_message_type'] === 'ContentItemSelectionRequest')) {
						$form_params = array();
						if (isset($this->request->data['data'])) {
							$form_params['data'] = $this->request->data['data'];
						}
						$version = (!empty($this->request->data['lti_version'])) ? $this->request->data['lti_version'] : Provider::LTI_VERSION1;
						$form_params = $this->Consumer->signParameters($error_url, 'ContentItemSelection', $version, $form_params);
						$this->set('url', $error_url);
						$this->set('params', $form_params);
						return $this->render('autoform');
					} else {
						$this->redirect($error_url);
					}
					exit;
				} else {
					if (!empty($this->Provider->error_output)) {
						$this->set('error', $this->Provider->error_output);
					} else if ($this->Provider->debugMode and !empty($this->Provider->reason)) {
						$this->set('error', $this->Provider->reason);
					} else {
						$this->set('message', $this->Provider->message);
					}
				}
			} else if (!empty($this->Provider->redirectURL)) {
				return $this->redirect($this->Provider->redirectURL);
			} else if (!empty($this->Provider->output)) {
				$this->set('output', $this->Provider->output);
			}
		}

	}

/**
 * Check if a share arrangement is in place.
 *
 * @return boolean True if no error is reported
 */
	protected function _checkForShare() {

		$doSaveResourceLink = TRUE;
		$data = $this->request->data;
		$key = $this->ResourceLink->primary_consumer_key;
		$id = $this->ResourceLink->primary_resource_link_id;

		$shareRequest = isset($data['custom_share_key']) && !empty($data['custom_share_key']);
		if ($shareRequest) {
			if (!$this->allowSharing) {
				$this->Provider->reason = 'Your sharing request has been refused because sharing is not being permitted.';
				return false;
			}
// Check if this is a new share key
			$this->loadModel('Lti.ShareKey');
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
						$this->LTIUser->getResourceLink()->primary_consumer_key = $key;
						$this->LTIUser->getResourceLink()->primary_resource_link_id = $id;
						$this->LTIUser->getResourceLink()->share_approved = $share_key->auto_approve;
						$this->LTIUser->getResourceLink()->modified = time();
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
					$ok = (!is_null($this->LTIUser->getResourceLink()->share_approved) && $this->LTIUser->getResourceLink()->share_approved);
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
