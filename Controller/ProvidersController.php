<?php
App::uses('LtiAppController', 'Lti.Controller');

class ProvidersController extends LtiAppController {
	public $components = ['Lti.LtiRequest'];
	public $scaffold = 'admin';
/**
 * Security
 *
 * @var array
 */
 	public $actions = [
 		'all' => [
 			'request', 'salesforce'
  		],
 		'admin' => [
 			'admin_index', 'admin_add', 'admin_edit', 'admin_delete',
 		],
 		'ajax-only' => [
 		]
	];

	public function beforeFilter() {
		$this->Security->unlockedActions = ['admin_index', 'admin_add', 'admin_edit', 'admin_delete', 'request', 'salesforce'];
		$this->Auth->allow('request', 'salesforce');

		parent::beforeFilter();
	}


	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}

	public function salesforce($consumerKey, $cohort=null) {
		$this->layout = 'basic';
		$this->request->data['oauth_consumer_key'] = $consumerKey;

		#
		### Perform action
		#

		$this->response->header('X-Frame-Options', '');
		$this->_init();
		
		$this->LtiRequest->initSalesforce($consumerKey);
		$this->LtiRequest->validate($cohort);

		if ($this->LtiRequest->authenticateSalesforce()) {

			$this->LtiRequest->setResourceLink();

			$this->LtiRequest->setUser();

			$this->LtiRequest->setConsumer();

			$this->LtiRequest->doCallbackMethod();

		} 
		$this->_result();

	}

	public function request($cohort=null) {
		$this->layout = 'basic';
		#
		### Perform action
		#

		$this->response->header('X-Frame-Options', '');
		$this->_init();
		$this->LtiRequest->validate($cohort);

		if ($this->LtiRequest->authenticate()) {

			$this->LtiRequest->setResourceLink();

			$this->LtiRequest->setUser();

			$this->LtiRequest->setConsumer();

			$this->LtiRequest->doCallbackMethod();

		}
		$this->_result();

	}


###
###  INTERNAL METHODS
###

	protected function _init() {
		$this->Provider->callbackHandler = array();
		if (!empty(Configure::read('Lti.callbackHandler'))) {
			$callbackHandler = Configure::read('Lti.callbackHandler');
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
		if (true || !empty($this->request->data['custom_debug'])) {
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

	/**
	 * Perform the result of an action.
	 *
	 * This function may redirect the user to another URL rather than returning a value.
	 *
	 * @return string Output to be displayed (redirection, or display HTML or message)
	 */
	public function _result() {
		if ($this->Provider->isOK) {
			if (!empty($this->Provider->redirectURL)) {
				return $this->redirect($this->Provider->redirectURL);
			}
			if (!empty($this->Provider->output)) {
				if ($this->Provider->isJsonOutput) {
					$this->layout = false;
					$this->autoRender = false;
					$this->set('output', json_encode($this->Provider->output));
					$this->header('Content-Type: application/json');
					$this->render('json');
					return;
				}
				$this->set('output', $this->Provider->output);
				return;
			}
		}

		$this->LtiRequest->doCallbackMethod('error');

		if ($this->Provider->isJsonOutput) {
			$this->layout = false;
			$this->autoRender = false;
			$this->set('output', json_encode(['error' => $this->Provider->error_output, 'reason' =>  $this->Provider->reason, 'message' =>  $this->Provider->message]));
			$this->header('Content-Type: application/json');
			$this->render('json');
			return;
		}

		#
		### If not valid, return an error message to the tool consumer if a return URL is provided
		#
		if (empty($this->Provider->return_url)) {
			if (!empty($this->Provider->error_output)) {
				$this->set('error', $this->Provider->error_output);
			} else if ($this->Provider->debugMode and !empty($this->Provider->reason)) {
				$this->set('error', $this->Provider->reason);
			} else {
				$this->set('message', $this->Provider->message);
			}

			return;
		}

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
	}
}
