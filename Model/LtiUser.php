<?php
App::uses('LtiAppModel', 'Lti.Model');

class LtiUser extends LtiAppModel {

	public $actsAs = ['Containable'];
	public $useTable = 'users';

	public $belongsTo = [
		'Consumer' => [
			'className' => 'Lti.Consumer',
			'dependent' => true,
			'foreignKey' => 'consumer_key'
		],
		'Context' => [
			'className' => 'Lti.Context',
			'dependent' => true,
		],
		'User' => [
			'className' => 'User',
			'dependent' => true,
		],
	];


/**
 * @var string User's first name.
 */
	public $firstname = '';
/**
 * @var string User's last name (surname or family name).
 */
	public $lastname = '';
/**
 * @var string User's fullname.
 */
	public $fullname = '';
/**
 * @var string User's email address.
 */
	public $email = '';
/**
 * @var array Roles for user.
 */
	public $roles = array();
/**
 * @var array Groups for user.
 */
	public $groups = array();
/**
 * @var string User's result sourcedid.
 */
	public $lis_result_sourcedid = NULL;
/**
 * @var object Date/time the record was created.
 */
	public $created = NULL;
/**
 * @var object Date/time the record was last modified.
 */
	public $modified = NULL;

/**
 * Set the user's name.
 *
 * @param string $firstname User's first name.
 * @param string $lastname User's last name.
 * @param string $fullname User's full name.
 */
	public function setNames($firstname, $lastname, $fullname) {

		$names = array(0 => '', 1 => '');
		if (!empty($fullname)) {
			$this->fullname = trim($fullname);
			$names = preg_split("/[\s]+/", $this->fullname, 2);
		}
		if (!empty($firstname)) {
			$this->firstname = trim($firstname);
			$names[0] = $this->firstname;
		} else if (!empty($names[0])) {
			$this->firstname = $names[0];
		} else {
			$this->firstname = 'User';
		}
		if (!empty($lastname)) {
			$this->lastname = trim($lastname);
			$names[1] = $this->lastname;
		} else if (!empty($names[1])) {
			$this->lastname = $names[1];
		} else {
			$this->lastname = $this->id;
		}
		if (empty($this->fullname)) {
			$this->fullname = "{$this->firstname} {$this->lastname}";
		}
		$this->data['firstname'] = $this->firstname;
		$this->data['lastname'] = $this->lastname;
		$this->data['fullname'] = $this->fullname;

	}

/**
 * Set the user's email address.
 *
 * @param string $email        Email address value
 * @param string $defaultEmail Value to use if no email is provided (optional, default is none)
 */
	public function setEmail($email, $defaultEmail = NULL) {

		if (!empty($email)) {
			$this->email = $email;
		} else if (!empty($defaultEmail)) {
			$this->email = $defaultEmail;
			if (substr($this->email, 0, 1) == '@') {
				$this->email = $this->getId() . $this->email;
			}
		} else {
			$this->email = '';
		}
		$this->data['email'] = $this->email;
	}

/**
 * Check if the user is an administrator (at any of the system, institution or context levels).
 *
 * @return boolean True if the user has a role of administrator
 */
	public function isAdmin() {

		return $this->hasRole('Administrator') || $this->hasRole('urn:lti:sysrole:ims/lis/SysAdmin') ||
					 $this->hasRole('urn:lti:sysrole:ims/lis/Administrator') || $this->hasRole('urn:lti:instrole:ims/lis/Administrator');

	}

/**
 * Check if the user is staff.
 *
 * @return boolean True if the user has a role of instructor, contentdeveloper or teachingassistant
 */
	public function isStaff() {

		return ($this->hasRole('Instructor') || $this->hasRole('ContentDeveloper') || $this->hasRole('TeachingAssistant'));

	}

/**
 * Check if the user is a learner.
 *
 * @return boolean True if the user has a role of learner
 */
	public function isLearner() {

		return $this->hasRole('Learner');

	}

###
###  PRIVATE METHODS
###

/**
 * Check whether the user has a specified role name.
 *
 * @param string $role Name of role
 *
 * @return boolean True if the user has the specified role
 */
	public function hasRole($role) {

		if (substr($role, 0, 4) != 'urn:') {
			$role = 'urn:lti:role:ims/lis/' . $role;
		}

		return in_array($role, $this->roles);

	}
###
###  LTI_User methods
###

// ###
// #    Load the user from the database
// ###
// 	public function load($user) {

// 		$key = $user->getResourceLink()->getKey();
// 		$id = $user->getResourceLink()->getId();
// 		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
// 		$sql = 'SELECT lis_result_sourcedid, created, modified ' .
// 					 'FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
// 					 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
// 		$query = $this->db->prepare($sql);
// 		$query->bindValue('key', $key, PDO::PARAM_STR);
// 		$query->bindValue('id', $id, PDO::PARAM_STR);
// 		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
// 		$ok = $query->execute();
// 		if ($ok) {
// 			$row = $query->fetch(PDO::FETCH_ASSOC);
// 			$ok = ($row !== FALSE);
// 		}

// 		if ($ok) {
// 			$row = array_change_key_case($row);
// 			$user->lis_result_sourcedid = $row['lis_result_sourcedid'];
// 			$user->created = strtotime($row['created']);
// 			$user->modified = strtotime($row['modified']);
// 		}

// 		return $ok;

// 	}

// ###
// #    Save the user to the database
// ###
// 	public function save($user) {

// 		$time = time();
// 		$now = date("{$this->date_format} {$this->time_format}", $time);
// 		$key = $user->getResourceLink()->getKey();
// 		$id = $user->getResourceLink()->getId();
// 		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
// 		if (is_null($user->created)) {
// 			$sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' (consumer_key, context_id, ' .
// 						 'user_id, lis_result_sourcedid, created, modified) ' .
// 						 'VALUES (:key, :id, :user_id, :lis_result_sourcedid, :now, :now)';
// 		} else {
// 			$sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
// 						 'SET lis_result_sourcedid = :lis_result_sourcedid, modified = :now ' .
// 						 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
// 		}
// 		$query = $this->db->prepare($sql);
// 		$query->bindValue('key', $key, PDO::PARAM_STR);
// 		$query->bindValue('id', $id, PDO::PARAM_STR);
// 		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
// 		$query->bindValue('lis_result_sourcedid', $user->lis_result_sourcedid, PDO::PARAM_STR);
// 		$query->bindValue('now', $now, PDO::PARAM_STR);
// 		$ok = $query->execute();
// 		if ($ok) {
// 			if (is_null($user->created)) {
// 				$user->created = $time;
// 			}
// 			$user->modified = $time;
// 		}

// 		return $ok;

// 	}

// ###
// #    Delete the user from the database
// ###
// 	public function delete($user) {

// 		$key = $user->getResourceLink()->getKey();
// 		$id = $user->getResourceLink()->getId();
// 		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
// 		$sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
// 					 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
// 		$query = $this->db->prepare($sql);
// 		$query->bindValue('key', $key, PDO::PARAM_STR);
// 		$query->bindValue('id', $id, PDO::PARAM_STR);
// 		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
// 		$ok = $query->execute();

// 		if ($ok) {
// 			$user->initialise();
// 		}

// 		return $ok;

// 	}

}
