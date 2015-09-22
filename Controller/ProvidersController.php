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

			$this->_doCallback();
		}
		$this->_result();

	}


	###
	###  HANDLER METHODS
	###

	/**
	 * Process a valid launch request
	 *
	 * @return boolean True if no error
	 */
		protected function _onLaunch() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid configure request
	 *
	 * @return boolean True if no error
	 */
		protected function _onConfigure() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid dashboard request
	 *
	 * @return boolean True if no error
	 */
		protected function _onDashboard() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid content-item request
	 *
	 * @return boolean True if no error
	 */
		protected function _onContentItem() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a response to an invalid request
	 *
	 * @return boolean True if no further error processing required
	 */
		protected function _onError() {

			$this->_doCallbackMethod('error');

		}

###
###  INTERNAL METHODS
###

	protected function _init() {
		$this->Provider->callbackHandler = array();
		if (!empty($this->request->data['callbackHandler'])) {
			$callbackHandler = $this->request->data['callbackHandler'];
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
		$this->Consumer->findByConsumerKey($data['oauth_consumer_key']);
		if (empty($this->Consumer->consumer_key)) {
			return $this->Provider->reason = 'Invalid consumer key.';
		}

		if ($this->Consumer->protected) {
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

		if (!empty($this->Consumer->enable_from) and ($this->Consumer->enable_from > $now)) {
			return $this->Provider->reason = 'Tool consumer access is not yet available.';
		}

		if (!empty($this->Consumer->enable_until) and ($this->Consumer->enable_until <= $now)) {
			return $this->Provider->reason = 'Tool consumer access has expired.';
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
		$content_item_id = '';
		if (!empty($data['custom_content_item_id'])) {
			$content_item_id = $data['custom_content_item_id'];
		}

		// first try loading the resource link for the specific content item
		$this->loadModel('Lti.ResourceLink');
		$this->ResourceLink->id = trim($data['resource_link_id']);
		$conditions =  [
				'consumer_key' => $this->Consumer->consumer_key,
				'context_id' => $this->ResourceLink->id
		];
		$link = $this->ResourceLink->find('first', ['conditions' => $conditions]);

		// however if we can't find this, load for the
		if (empty($link) and !empty($content_item_id)) {
			$conditions['context_id'] = $this->ResourceLink->id = $content_item_id;
			$link = $this->ResourceLink->find('first', ['conditions' => $conditions]);
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
			$title = "Course {$this->ResourceLink->id}";
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
		$this->Provider->isOK = $this->_checkForShare();

		#
		### Persist changes to resource link
		#
		$this->ResourceLink->save();
	}

	protected function _setUser() {
		$data = $this->request->data;
		#
		### Set the user instance
		#
		$user_id = '';
		if (isset($data['user_id'])) {
			$user_id = trim($data['user_id']);
		}
		$this->LTIUser = new LTIUser($this->resource_link, $user_id);

		#
		### Set the user name
		#
		$firstname = (isset($data['lis_person_name_given'])) ? $data['lis_person_name_given'] : '';
		$lastname = (isset($data['lis_person_name_family'])) ? $data['lis_person_name_family'] : '';
		$fullname = (isset($data['lis_person_name_full'])) ? $data['lis_person_name_full'] : '';
		$this->LTIUser->setNames($firstname, $lastname, $fullname);

		#
		### Set the user email
		#
		$email = (isset($data['lis_person_contact_email_primary'])) ? $data['lis_person_contact_email_primary'] : '';
		$this->LTIUser->setEmail($email, $this->defaultEmail);

		#
		### Set the user roles
		#
		if (isset($data['roles'])) {
			$this->LTIUser->roles = $this->Provider->parseRoles($data['roles']);
		}

		#
		### Save the user instance
		#
		if (isset($data['lis_result_sourcedid'])) {
			if ($this->user->lti_result_sourcedid != $data['lis_result_sourcedid']) {
				$this->user->lti_result_sourcedid = $data['lis_result_sourcedid'];
				$this->user->save();
			}
		} else if (!empty($this->user->lti_result_sourcedid)) {
			$this->user->delete();
		}

	}

	protected function _setConsumer() {
				#
		### Initialise the consumer and check for changes
		#
		$doSaveConsumer = FALSE;

		$now = time();
		$today = date('Y-m-d', $now);
		if (empty($this->Consumer->last_access)) {
			$doSaveConsumer = TRUE;
		} else {
			$last = date('Y-m-d', $this->Consumer->last_access);
			$doSaveConsumer = $doSaveConsumer || ($last != $today);
		}
		$this->Consumer->last_access = $now;
		$this->consumer->defaultEmail = $this->defaultEmail;
		if ($this->consumer->lti_version != $data['lti_version']) {
			$this->consumer->lti_version = $data['lti_version'];
			$doSaveConsumer = TRUE;
		}
		if (isset($data['tool_consumer_instance_name'])) {
			if ($this->consumer->consumer_name != $data['tool_consumer_instance_name']) {
				$this->consumer->consumer_name = $data['tool_consumer_instance_name'];
				$doSaveConsumer = TRUE;
			}
		}
		if (isset($data['tool_consumer_info_product_family_code'])) {
			$version = $data['tool_consumer_info_product_family_code'];
			if (isset($data['tool_consumer_info_version'])) {
				$version .= "-{$data['tool_consumer_info_version']}";
			}

			// do not delete any existing consumer version if none is passed
			if ($this->consumer->consumer_version != $version) {
				$this->consumer->consumer_version = $version;
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_lms']) && ($this->consumer->consumer_name != $data['ext_lms'])) {
			$this->consumer->consumer_version = $data['ext_lms'];
			$doSaveConsumer = TRUE;
		}

		if (isset($data['tool_consumer_instance_guid'])) {
			if (is_null($this->consumer->consumer_guid)) {
				$this->consumer->consumer_guid = $data['tool_consumer_instance_guid'];
				$doSaveConsumer = TRUE;
			} else if (!$this->consumer->protected) {
				$doSaveConsumer = ($this->consumer->consumer_guid != $data['tool_consumer_instance_guid']);
				if ($doSaveConsumer) {
					$this->consumer->consumer_guid = $data['tool_consumer_instance_guid'];
				}
			}
		}

		if (isset($data['launch_presentation_css_url'])) {
			if ($this->consumer->css_path != $data['launch_presentation_css_url']) {
				$this->consumer->css_path = $data['launch_presentation_css_url'];
				$doSaveConsumer = TRUE;
			}
		} else if (isset($data['ext_launch_presentation_css_url']) &&
			 ($this->consumer->css_path != $data['ext_launch_presentation_css_url'])) {
			$this->consumer->css_path = $data['ext_launch_presentation_css_url'];
			$doSaveConsumer = TRUE;
		} else if (!empty($this->consumer->css_path)) {
			$this->consumer->css_path = NULL;
			$doSaveConsumer = TRUE;
		}

		#
		### Persist changes to consumer
		#
		if ($doSaveConsumer) {
			$this->consumer->save();
		}

	}
/**
 * Call any callback function for the requested action.
 *
 * This function may set the redirectURL and output properties.
 *
 * @return boolean True if no error reported
 */
	protected function _doCallback() {

		$method = "_" . $this->Provider->methodNames[$this->request->data['lti_message_type']];
		$this->$method();

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
		if (isset($this->Provider->callbackHandler[$callback])) {
			$result = call_user_func($this->Provider->callbackHandler[$callback], $this);

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
		} else if (is_null($type) && $this->Provider->isOK) {
			$this->Provider->isOK = FALSE;
			$this->Provider->reason = 'Message type not supported.';
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
			$ok = $this->_onError();
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
					if ($this->Provider->debugMode && !is_null($this->Provider->reason)) {
						$error_url .= 'lti_errormsg=' . urlencode("Debug error: {$this->Provider->reason}");
					} else {
						$error_url .= 'lti_errormsg=' . urlencode($this->Provider->message);
						if (!is_null($this->Provider->reason)) {
							$error_url .= '&lti_errorlog=' . urlencode("Debug error: {$this->Provider->reason}");
						}
					}
					if (!is_null($this->Consumer) && isset($this->request->data['lti_message_type']) && ($this->request->data['lti_message_type'] === 'ContentItemSelectionRequest')) {
						$form_params = array();
						if (isset($this->request->data['data'])) {
							$form_params['data'] = $this->request->data['data'];
						}
						$version = (isset($this->request->data['lti_version'])) ? $this->request->data['lti_version'] : Provider::LTI_VERSION1;
						$form_params = $this->Consumer->signParameters($error_url, 'ContentItemSelection', $version, $form_params);
						$this->set('url', $error_url);
						$this->set('params', $form_params);
						return $this->render('autoform');
					} else {
						$this->redirect($error_url);
					}
					exit;
				} else {
					if (!is_null($this->Provider->error_output)) {
						$this->set('error', $this->Provider->error_output);
					} else if ($this->Provider->debugMode && !empty($this->Provider->reason)) {
						$this->set('error', $this->Provider->reason);
					} else {
						$this->set('message', $this->Provider->message);
					}
				}
			} else if (!is_null($this->Provider->redirectURL)) {
				return $this->redirect($this->Provider->redirectURL);
			} else if (!is_null($this->Provider->output)) {
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

		$ok = TRUE;
		$doSaveResourceLink = TRUE;

		$key = $this->resource_link->primary_consumer_key;
		$id = $this->resource_link->primary_resource_link_id;

		$shareRequest = isset($data['custom_share_key']) && !empty($data['custom_share_key']);
		if ($shareRequest) {
			if (!$this->allowSharing) {
				$ok = FALSE;
				$this->reason = 'Your sharing request has been refused because sharing is not being permitted.';
			} else {
// Check if this is a new share key
				$share_key = new LTI_Resource_Link_Share_Key($this->resource_link, $data['custom_share_key']);
				if (!is_null($share_key->primary_consumer_key) && !is_null($share_key->primary_resource_link_id)) {
// Update resource link with sharing primary resource link details
					$key = $share_key->primary_consumer_key;
					$id = $share_key->primary_resource_link_id;
					$ok = ($key != $this->consumer->getKey()) || ($id != $this->resource_link->getId());
					if ($ok) {
						$this->resource_link->primary_consumer_key = $key;
						$this->resource_link->primary_resource_link_id = $id;
						$this->resource_link->share_approved = $share_key->auto_approve;
						$ok = $this->resource_link->save();
						if ($ok) {
							$doSaveResourceLink = FALSE;
							$this->User->getResourceLink()->primary_consumer_key = $key;
							$this->User->getResourceLink()->primary_resource_link_id = $id;
							$this->User->getResourceLink()->share_approved = $share_key->auto_approve;
							$this->User->getResourceLink()->modified = time();
// Remove share key
							$share_key->delete();
						} else {
							$this->reason = 'An error occurred initialising your share arrangement.';
						}
					} else {
						$this->reason = 'It is not possible to share your resource link with yourself.';
					}
				}
				if ($ok) {
					$ok = !is_null($key);
					if (!$ok) {
						$this->reason = 'You have requested to share a resource link but none is available.';
					} else {
						$ok = (!is_null($this->user->getResourceLink()->share_approved) && $this->user->getResourceLink()->share_approved);
						if (!$ok) {
							$this->reason = 'Your share request is waiting to be approved.';
						}
					}
				}
			}
		} else {
// Check no share is in place
			$ok = is_null($key);
			if (!$ok) {
				$this->reason = 'You have not requested to share a resource link but an arrangement is currently in place.';
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
					$this->resource_link->save();
				}
				$this->resource_link = $resource_link;
			} else {
				$this->reason = 'Unable to load resource link being shared.';
			}
		}

		return $ok;

	}


}
