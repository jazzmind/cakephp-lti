<?php
App::uses('LtiAppController', 'Lti.Controller');

class ProvidersController extends LtiAppController {
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


	public function request() {

		#
		### Perform action
		#
		$this->_init();

		if ($this->Provider->isOK) {
			if ($this->_authenticate()) {
				$this->_doCallback();
			}
		}
		$this->_result();

	}

	protected function _init() {

		$callbackHandler = $this->request->data['callbackHandler'];
		$this->callbackHandler = array();
		if (is_array($callbackHandler)) {
			$this->callbackHandler = $callbackHandler;
			if (isset($this->callbackHandler['connect']) && !isset($this->callbackHandler['launch'])) {  // for backward compatibility
				$this->callbackHandler['launch'] = $this->callbackHandler['connect'];
				unset($this->callbackHandler['connect']);
			}
		} else if (!empty($callbackHandler)) {
			$this->callbackHandler['launch'] = $callbackHandler;
		}
		#
		### Set debug mode
		#
		$this->debugMode = isset($this->request->data['custom_debug']) && (strtolower($this->request->data['custom_debug']) == 'true');
		### Set return URL if available
		#
		if (isset($this->request->data['launch_presentation_return_url'])) {
			$this->return_url = $this->request->data['launch_presentation_return_url'];
		} else if (isset($this->request->data['content_item_return_url'])) {
			$this->return_url = $this->request->data['content_item_return_url'];
		}

	}



	###
	###  PROTECTED METHODS
	###

	/**
	 * Process a valid launch request
	 *
	 * @return boolean True if no error
	 */
		protected function onLaunch() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid configure request
	 *
	 * @return boolean True if no error
	 */
		protected function onConfigure() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid dashboard request
	 *
	 * @return boolean True if no error
	 */
		protected function onDashboard() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a valid content-item request
	 *
	 * @return boolean True if no error
	 */
		protected function onContentItem() {

			$this->_doCallbackMethod();

		}

	/**
	 * Process a response to an invalid request
	 *
	 * @return boolean True if no further error processing required
	 */
		protected function onError() {

			$this->_doCallbackMethod('error');

		}

###
###  PRIVATE METHODS
###

/**
 * Call any callback function for the requested action.
 *
 * This function may set the redirectURL and output properties.
 *
 * @return boolean True if no error reported
 */
	protected function _doCallback() {

		$method = $this->Provider->methodNames[$this->request->data['lti_message_type']];
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
	private function result() {

		$ok = FALSE;
		if (!$this->Provider->isOK) {
			$ok = $this->onError();
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
 * Check the authenticity of the LTI launch request.
 *
 * The consumer, resource link and user objects will be initialised if the request is valid.
 *
 * @return boolean True if the request has been successfully validated.
 */
	private function authenticate() {

#
### Get the consumer
#
		$doSaveConsumer = FALSE;
// Check all required launch parameters
		$this->isOK = isset($_POST['lti_message_type']) && array_key_exists($_POST['lti_message_type'], $this->messageTypes);
		if (!$this->isOK) {
			$this->reason = 'Invalid or missing lti_message_type parameter.';
		}
		if ($this->isOK) {
			$this->isOK = isset($_POST['lti_version']) && in_array($_POST['lti_version'], $this->LTI_VERSIONS);
			if (!$this->isOK) {
				$this->reason = 'Invalid or missing lti_version parameter.';
			}
		}
		if ($this->isOK) {
			if (($_POST['lti_message_type'] == 'basic-lti-launch-request') || ($_POST['lti_message_type'] == 'DashboardRequest')) {
				$this->isOK = isset($_POST['resource_link_id']) && (strlen(trim($_POST['resource_link_id'])) > 0);
				if (!$this->isOK) {
					$this->reason = 'Missing resource link ID.';
				}
			} else if ($_POST['lti_message_type'] == 'ContentItemSelectionRequest') {
				if (isset($_POST['accept_media_types']) && (strlen(trim($_POST['accept_media_types'])) > 0)) {
					$mediaTypes = array_filter(explode(',', str_replace(' ', '', $_POST['accept_media_types'])), 'strlen');
					$mediaTypes = array_unique($mediaTypes);
					$this->isOK = count($mediaTypes) > 0;
					if (!$this->isOK) {
						$this->reason = 'No accept_media_types found.';
					} else {
						$this->mediaTypes = $mediaTypes;
					}
				} else {
					$this->isOK = FALSE;
				}
				if ($this->isOK && isset($_POST['accept_presentation_document_targets']) && (strlen(trim($_POST['accept_presentation_document_targets'])) > 0)) {
					$documentTargets = array_filter(explode(',', str_replace(' ', '', $_POST['accept_presentation_document_targets'])), 'strlen');
					$documentTargets = array_unique($documentTargets);
					$this->isOK = count($documentTargets) > 0;
					if (!$this->isOK) {
						$this->reason = 'Missing or empty accept_presentation_document_targets parameter.';
					} else {
						foreach ($documentTargets as $documentTarget) {
							$this->isOK = $this->checkValue($documentTarget, array('embed', 'frame', 'iframe', 'window', 'popup', 'overlay', 'none'),
								 'Invalid value in accept_presentation_document_targets parameter: %s.');
							if (!$this->isOK) {
								break;
							}
						}
						if ($this->isOK) {
							$this->documentTargets = $documentTargets;
						}
					}
				} else {
					$this->isOK = FALSE;
				}
				if ($this->isOK) {
					$this->isOK = isset($_POST['content_item_return_url']) && (strlen(trim($_POST['content_item_return_url'])) > 0);
					if (!$this->isOK) {
						$this->reason = 'Missing content_item_return_url parameter.';
					}
				}
			}
		}
// Check consumer key
		if ($this->isOK) {
			$this->isOK = isset($_POST['oauth_consumer_key']);
			if (!$this->isOK) {
				$this->reason = 'Missing consumer key.';
			}
		}
		if ($this->isOK) {
			$this->consumer = new LTI_Tool_Consumer($_POST['oauth_consumer_key'], $this->data_connector);
			$this->isOK = !is_null($this->consumer->created);
			if (!$this->isOK) {
				$this->reason = 'Invalid consumer key.';
			}
		}
		$now = time();
		if ($this->isOK) {
			$today = date('Y-m-d', $now);
			if (is_null($this->consumer->last_access)) {
				$doSaveConsumer = TRUE;
			} else {
				$last = date('Y-m-d', $this->consumer->last_access);
				$doSaveConsumer = $doSaveConsumer || ($last != $today);
			}
			$this->consumer->last_access = $now;
			try {
				$store = new LTI_OAuthDataStore($this);
				$server = new OAuthServer($store);
				$method = new OAuthSignatureMethod_HMAC_SHA1();
				$server->add_signature_method($method);
				$request = OAuthRequest::from_request();
				$res = $server->verify_request($request);
			} catch (Exception $e) {
				$this->isOK = FALSE;
				if (empty($this->reason)) {
					if ($this->debugMode) {
						$consumer = new OAuthConsumer($this->consumer->getKey(), $this->consumer->secret);
						$signature = $request->build_signature($method, $consumer, FALSE);
						$this->reason = $e->getMessage();
						if (empty($this->reason)) {
							$this->reason = 'OAuth exception';
						}
						$this->details[] = 'Timestamp: ' . time();
						$this->details[] = "Signature: {$signature}";
						$this->details[] = "Base string: {$request->base_string}]";
					} else {
						$this->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
					}
				}
			}
		}
		if ($this->isOK && $this->consumer->protected) {
			if (!is_null($this->consumer->consumer_guid)) {
				$this->isOK = isset($_POST['tool_consumer_instance_guid']) && !empty($_POST['tool_consumer_instance_guid']) &&
					 ($this->consumer->consumer_guid == $_POST['tool_consumer_instance_guid']);
				if (!$this->isOK) {
					$this->reason = 'Request is from an invalid tool consumer.';
				}
			} else {
				$this->isOK = isset($_POST['tool_consumer_instance_guid']);
				if (!$this->isOK) {
					$this->reason = 'A tool consumer GUID must be included in the launch request.';
				}
			}
		}
		if ($this->isOK) {
			$this->isOK = $this->consumer->enabled;
			if (!$this->isOK) {
				$this->reason = 'Tool consumer has not been enabled by the tool provider.';
			}
		}
		if ($this->isOK) {
			$this->isOK = is_null($this->consumer->enable_from) || ($this->consumer->enable_from <= $now);
			if ($this->isOK) {
				$this->isOK = is_null($this->consumer->enable_until) || ($this->consumer->enable_until > $now);
				if (!$this->isOK) {
					$this->reason = 'Tool consumer access has expired.';
				}
			} else {
				$this->reason = 'Tool consumer access is not yet available.';
			}
		}

#
### Validate other message parameter values
#
		if ($this->isOK) {
			if ($_POST['lti_message_type'] != 'ContentItemSelectionRequest') {
				if (isset($_POST['launch_presentation_document_target'])) {
					$this->isOK = $this->checkValue($_POST['launch_presentation_document_target'], array('embed', 'frame', 'iframe', 'window', 'popup', 'overlay'),
						 'Invalid value for launch_presentation_document_target parameter: %s.');
				}
			} else {
				if (isset($_POST['accept_unsigned'])) {
					$this->isOK = $this->checkValue($_POST['accept_unsigned'], array('true', 'false'), 'Invalid value for accept_unsigned parameter: %s.');
				}
				if ($this->isOK && isset($_POST['accept_multiple'])) {
					$this->isOK = $this->checkValue($_POST['accept_multiple'], array('true', 'false'), 'Invalid value for accept_multiple parameter: %s.');
				}
				if ($this->isOK && isset($_POST['accept_copy_advice'])) {
					$this->isOK = $this->checkValue($_POST['accept_copy_advice'], array('true', 'false'), 'Invalid value for accept_copy_advice parameter: %s.');
				}
				if ($this->isOK && isset($_POST['auto_create'])) {
					$this->isOK = $this->checkValue($_POST['auto_create'], array('true', 'false'), 'Invalid value for auto_create parameter: %s.');
				}
				if ($this->isOK && isset($_POST['can_confirm'])) {
					$this->isOK = $this->checkValue($_POST['can_confirm'], array('true', 'false'), 'Invalid value for can_confirm parameter: %s.');
				}
			}
		}

#
### Validate message parameter constraints
#
		if ($this->isOK) {
			$invalid_parameters = array();
			foreach ($this->constraints as $name => $constraint) {
				if (empty($constraint['messages']) || in_array($_POST['lti_message_type'], $constraint['messages'])) {
					$ok = TRUE;
					if ($constraint['required']) {
						if (!isset($_POST[$name]) || (strlen(trim($_POST[$name])) <= 0)) {
							$invalid_parameters[] = "{$name} (missing)";
							$ok = FALSE;
						}
					}
					if ($ok && !is_null($constraint['max_length']) && isset($_POST[$name])) {
						if (strlen(trim($_POST[$name])) > $constraint['max_length']) {
							$invalid_parameters[] = "{$name} (too long)";
						}
					}
				}
			}
			if (count($invalid_parameters) > 0) {
				$this->isOK = FALSE;
				if (empty($this->reason)) {
					$this->reason = 'Invalid parameter(s): ' . implode(', ', $invalid_parameters) . '.';
				}
			}
		}

		if ($this->isOK) {
#
### Set the request context/resource link
#
			if (isset($_POST['resource_link_id'])) {
				$content_item_id = '';
				if (isset($_POST['custom_content_item_id'])) {
					$content_item_id = $_POST['custom_content_item_id'];
				}
				$this->resource_link = new LTI_Resource_Link($this->consumer, trim($_POST['resource_link_id']), $content_item_id);
				if (isset($_POST['context_id'])) {
					$this->resource_link->lti_context_id = trim($_POST['context_id']);
				}
				$this->resource_link->lti_resource_id = trim($_POST['resource_link_id']);
				$title = '';
				if (isset($_POST['context_title'])) {
					$title = trim($_POST['context_title']);
				}
				if (isset($_POST['resource_link_title']) && (strlen(trim($_POST['resource_link_title'])) > 0)) {
					if (!empty($title)) {
						$title .= ': ';
					}
					$title .= trim($_POST['resource_link_title']);
				}
				if (empty($title)) {
					$title = "Course {$this->resource_link->getId()}";
				}
				$this->resource_link->title = $title;
// Save LTI parameters
				foreach ($this->lti_settings_names as $name) {
					if (isset($_POST[$name])) {
						$this->resource_link->setSetting($name, $_POST[$name]);
					} else {
						$this->resource_link->setSetting($name, NULL);
					}
				}
// Delete any existing custom parameters
				foreach ($this->resource_link->getSettings() as $name => $value) {
					if (strpos($name, 'custom_') === 0) {
						$this->resource_link->setSetting($name);
					}
				}
// Save custom parameters
				foreach ($_POST as $name => $value) {
					if (strpos($name, 'custom_') === 0) {
						$this->resource_link->setSetting($name, $value);
					}
				}
			}
#
### Set the user instance
#
			$user_id = '';
			if (isset($_POST['user_id'])) {
				$user_id = trim($_POST['user_id']);
			}
			$this->user = new LTI_User($this->resource_link, $user_id);
#
### Set the user name
#
			$firstname = (isset($_POST['lis_person_name_given'])) ? $_POST['lis_person_name_given'] : '';
			$lastname = (isset($_POST['lis_person_name_family'])) ? $_POST['lis_person_name_family'] : '';
			$fullname = (isset($_POST['lis_person_name_full'])) ? $_POST['lis_person_name_full'] : '';
			$this->user->setNames($firstname, $lastname, $fullname);
#
### Set the user email
#
			$email = (isset($_POST['lis_person_contact_email_primary'])) ? $_POST['lis_person_contact_email_primary'] : '';
			$this->user->setEmail($email, $this->defaultEmail);
#
### Set the user roles
#
			if (isset($_POST['roles'])) {
				$this->user->roles = LTI_Tool_Provider::parseRoles($_POST['roles']);
			}
#
### Save the user instance
#
			if (isset($_POST['lis_result_sourcedid'])) {
				if ($this->user->lti_result_sourcedid != $_POST['lis_result_sourcedid']) {
					$this->user->lti_result_sourcedid = $_POST['lis_result_sourcedid'];
					$this->user->save();
				}
			} else if (!empty($this->user->lti_result_sourcedid)) {
				$this->user->delete();
			}
#
### Initialise the consumer and check for changes
#
			$this->consumer->defaultEmail = $this->defaultEmail;
			if ($this->consumer->lti_version != $_POST['lti_version']) {
				$this->consumer->lti_version = $_POST['lti_version'];
				$doSaveConsumer = TRUE;
			}
			if (isset($_POST['tool_consumer_instance_name'])) {
				if ($this->consumer->consumer_name != $_POST['tool_consumer_instance_name']) {
					$this->consumer->consumer_name = $_POST['tool_consumer_instance_name'];
					$doSaveConsumer = TRUE;
				}
			}
			if (isset($_POST['tool_consumer_info_product_family_code'])) {
				$version = $_POST['tool_consumer_info_product_family_code'];
				if (isset($_POST['tool_consumer_info_version'])) {
					$version .= "-{$_POST['tool_consumer_info_version']}";
				}
// do not delete any existing consumer version if none is passed
				if ($this->consumer->consumer_version != $version) {
					$this->consumer->consumer_version = $version;
					$doSaveConsumer = TRUE;
				}
			} else if (isset($_POST['ext_lms']) && ($this->consumer->consumer_name != $_POST['ext_lms'])) {
				$this->consumer->consumer_version = $_POST['ext_lms'];
				$doSaveConsumer = TRUE;
			}
			if (isset($_POST['tool_consumer_instance_guid'])) {
				if (is_null($this->consumer->consumer_guid)) {
					$this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
					$doSaveConsumer = TRUE;
				} else if (!$this->consumer->protected) {
					$doSaveConsumer = ($this->consumer->consumer_guid != $_POST['tool_consumer_instance_guid']);
					if ($doSaveConsumer) {
						$this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
					}
				}
			}
			if (isset($_POST['launch_presentation_css_url'])) {
				if ($this->consumer->css_path != $_POST['launch_presentation_css_url']) {
					$this->consumer->css_path = $_POST['launch_presentation_css_url'];
					$doSaveConsumer = TRUE;
				}
			} else if (isset($_POST['ext_launch_presentation_css_url']) &&
				 ($this->consumer->css_path != $_POST['ext_launch_presentation_css_url'])) {
				$this->consumer->css_path = $_POST['ext_launch_presentation_css_url'];
				$doSaveConsumer = TRUE;
			} else if (!empty($this->consumer->css_path)) {
				$this->consumer->css_path = NULL;
				$doSaveConsumer = TRUE;
			}
		}
#
### Persist changes to consumer
#
		if ($doSaveConsumer) {
			$this->consumer->save();
		}

		if ($this->isOK && isset($this->resource_link)) {
#
### Check if a share arrangement is in place for this resource link
#
			$this->isOK = $this->checkForShare();
#
### Persist changes to resource link
#
			$this->resource_link->save();
		}

		return $this->isOK;

	}

/**
 * Check if a share arrangement is in place.
 *
 * @return boolean True if no error is reported
 */
	private function checkForShare() {

		$ok = TRUE;
		$doSaveResourceLink = TRUE;

		$key = $this->resource_link->primary_consumer_key;
		$id = $this->resource_link->primary_resource_link_id;

		$shareRequest = isset($_POST['custom_share_key']) && !empty($_POST['custom_share_key']);
		if ($shareRequest) {
			if (!$this->allowSharing) {
				$ok = FALSE;
				$this->reason = 'Your sharing request has been refused because sharing is not being permitted.';
			} else {
// Check if this is a new share key
				$share_key = new LTI_Resource_Link_Share_Key($this->resource_link, $_POST['custom_share_key']);
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
