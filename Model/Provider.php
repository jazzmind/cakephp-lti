<?php
App::uses('LtiAppModel', 'Lti.Model');

class Provider extends LtiAppModel {

	public $actsAs = ['Containable'];
	public $table = false;

/**
 * Default connection error message.
 */
	const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

/**
 * LTI version 1 for messages.
 *
 * @deprecated Use LTI_VERSION1 instead
 * @see LTI_Tool_Provider::LTI_VERSION1
 */
	const LTI_VERSION = 'LTI-1p0';
/**
 * LTI version 1 for messages.
 */
	const LTI_VERSION1 = 'LTI-1p0';
/**
 * LTI version 2 for messages.
 */
	const LTI_VERSION2 = 'LTI-2p0';
/**
 * Use ID value only.
 */
	const ID_SCOPE_ID_ONLY = 0;
/**
 * Prefix an ID with the consumer key.
 */
	const ID_SCOPE_GLOBAL = 1;
/**
 * Prefix the ID with the consumer key and context ID.
 */
	const ID_SCOPE_CONTEXT = 2;
/**
 * Prefix the ID with the consumer key and resource ID.
 */
	const ID_SCOPE_RESOURCE = 3;
/**
 * Character used to separate each element of an ID.
 */
	const ID_SCOPE_SEPARATOR = ':';

/**
 *  @var boolean True if the last request was successful.
 */
	public $isOK = TRUE;
/**
 *  @var LTI_Tool_Consumer Tool Consumer object.
 */
	public $consumer = NULL;
/**
 *  @var string Return URL provided by tool consumer.
 */
	public $return_url = NULL;
/**
 *  @var LTI_User User object.
 */
	public $user = NULL;
/**
 *  @var LTI_Resource_Link Resource link object.
 */
	public $resource_link = NULL;
/**
 *  @var LTI_Context Resource link object.
 *
 *  @deprecated Use resource_link instead
 *  @see LTI_Tool_Provider::$resource_link
 */
	public $context = NULL;
/**
 *  @var LTI_Data_Connector Data connector object.
 */
	public $data_connector = NULL;
/**
 *  @var string Default email domain.
 */
	public $defaultEmail = '';
/**
 *  @var int Scope to use for user IDs.
 */
	public $id_scope = self::ID_SCOPE_ID_ONLY;
/**
 *  @var boolean Whether shared resource link arrangements are permitted.
 */
	public $allowSharing = FALSE;
/**
 *  @var string Message for last request processed
 */
	public $message = self::CONNECTION_ERROR_MESSAGE;
/**
 *  @var string Error message for last request processed.
 */
	public $reason = NULL;
/**
 *  @var array Details for error message relating to last request processed.
 */
	public $details = array();

/**
 *  @var string URL to redirect user to on successful completion of the request.
 */
	public $redirectURL = NULL;
/**
 *  @var string URL to redirect user to on successful completion of the request.
 */
	public $mediaTypes = NULL;
/**
 *  @var string URL to redirect user to on successful completion of the request.
 */
	public $documentTargets = NULL;
/**
 *  @var string HTML to be displayed on a successful completion of the request.
 */
	public $output = NULL;
/**
 *  @var string HTML to be displayed on an unsuccessful completion of the request and no return URL is available.
 */
	public $error_output = NULL;
/**
 *  @var boolean Whether debug messages explaining the cause of errors are to be returned to the tool consumer.
 */
	public $debugMode = FALSE;

/**
 *  @var array Callback functions for handling requests.
 */
	public $callbackHandler = NULL;
/**
 *  @var array LTI parameter constraints for auto validation checks.
 */
	public $constraints = NULL;
/**
 *  @var array List of supported message types and associated callback type names
 */
	public $messageTypes = [
		'basic-lti-launch-request' => 'launch',
		'ConfigureLaunchRequest' => 'configure',
		'DashboardRequest' => 'dashboard',
		'ContentItemSelectionRequest' => 'content-item',
		'APITokenRequest' => 'authenticate'
	];
/**
 *  @var array List of supported message types and associated class methods
 */
	public $methodNames = [
		'basic-lti-launch-request' => 'onLaunch',
		'ConfigureLaunchRequest' => 'onConfigure',
		'DashboardRequest' => 'onDashboard',
		'ContentItemSelectionRequest' => 'onContentItem',
		'APITokenRequest' => 'onAuthenticate'
	];
/**
 *  @var array Names of LTI parameters to be retained in the settings property.
 */
	public $lti_settings_names = [
		'ext_resource_link_content', 'ext_resource_link_content_signature',
		'lis_result_sourcedid', 'lis_outcome_service_url',
		'ext_ims_lis_basic_outcome_url', 'ext_ims_lis_resultvalue_sourcedids',
		'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
		'ext_ims_lti_tool_setting', 'ext_ims_lti_tool_setting_id', 'ext_ims_lti_tool_setting_url'
	];

/**
 * @var array Permitted LTI versions for messages.
 */
	public $LTI_VERSIONS = array(self::LTI_VERSION1, self::LTI_VERSION2);

/**
 * Add a parameter constraint to be checked on launch
 *
 * @param string $name          Name of parameter to be checked
 * @param boolean $required     True if parameter is required (optional, default is TRUE)
 * @param int $max_length       Maximum permitted length of parameter value (optional, default is NULL)
 * @param array $message_types  Array of message types to which the constraint applies (default is all)
 */
	public function setParameterConstraint($name, $required = TRUE, $max_length = NULL, $message_types = NULL) {

		$name = trim($name);
		if (strlen($name) > 0) {
			$this->constraints[$name] = array('required' => $required, 'max_length' => $max_length, 'messages' => $message_types);
		}

	}

/**
 * Get an array of fully qualified user roles
 *
 * @param string Comma-separated list of roles
 *
 * @return array Array of roles
 */
	public static function parseRoles($rolesString) {

		$rolesArray = explode(',', $rolesString);
		$roles = array();
		foreach ($rolesArray as $role) {
			$role = trim($role);
			if (!empty($role)) {
				if (substr($role, 0, 4) != 'urn:') {
					$role = 'urn:lti:role:ims/lis/' . $role;
				}
				$roles[] = $role;
			}
		}

		return $roles;

	}


/**
 * Validate a parameter value from an array of permitted values.
 *
 * @return boolean True if value is valid
 */
	private function checkValue($value, $values, $reason) {

		$ok = in_array($value, $values);
		if (!$ok && !empty($reason)) {
			$this->reason = sprintf($reason, $value);
		}

		return $ok;

	}
}
