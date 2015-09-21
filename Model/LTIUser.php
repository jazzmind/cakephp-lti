<?php
App::uses('LtiAppModel', 'Lti.Model');

class LtiUser extends LtiAppModel {

	public $actsAs = ['Containable'];



###
###  LTI_User methods
###

###
#    Load the user from the database
###
	public function load($user) {

		$key = $user->getResourceLink()->getKey();
		$id = $user->getResourceLink()->getId();
		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
		$sql = 'SELECT lti_result_sourcedid, created, modified ' .
					 'FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
					 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
		$query = $this->db->prepare($sql);
		$query->bindValue('key', $key, PDO::PARAM_STR);
		$query->bindValue('id', $id, PDO::PARAM_STR);
		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
		$ok = $query->execute();
		if ($ok) {
			$row = $query->fetch(PDO::FETCH_ASSOC);
			$ok = ($row !== FALSE);
		}

		if ($ok) {
			$row = array_change_key_case($row);
			$user->lti_result_sourcedid = $row['lti_result_sourcedid'];
			$user->created = strtotime($row['created']);
			$user->modified = strtotime($row['modified']);
		}

		return $ok;

	}

###
#    Save the user to the database
###
	public function save($user) {

		$time = time();
		$now = date("{$this->date_format} {$this->time_format}", $time);
		$key = $user->getResourceLink()->getKey();
		$id = $user->getResourceLink()->getId();
		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
		if (is_null($user->created)) {
			$sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' (consumer_key, context_id, ' .
						 'user_id, lti_result_sourcedid, created, modified) ' .
						 'VALUES (:key, :id, :user_id, :lti_result_sourcedid, :now, :now)';
		} else {
			$sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
						 'SET lti_result_sourcedid = :lti_result_sourcedid, modified = :now ' .
						 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
		}
		$query = $this->db->prepare($sql);
		$query->bindValue('key', $key, PDO::PARAM_STR);
		$query->bindValue('id', $id, PDO::PARAM_STR);
		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
		$query->bindValue('lti_result_sourcedid', $user->lti_result_sourcedid, PDO::PARAM_STR);
		$query->bindValue('now', $now, PDO::PARAM_STR);
		$ok = $query->execute();
		if ($ok) {
			if (is_null($user->created)) {
				$user->created = $time;
			}
			$user->modified = $time;
		}

		return $ok;

	}

###
#    Delete the user from the database
###
	public function delete($user) {

		$key = $user->getResourceLink()->getKey();
		$id = $user->getResourceLink()->getId();
		$userId = $user->getId(LTI_Tool_Provider::ID_SCOPE_ID_ONLY);
		$sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' ' .
					 'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
		$query = $this->db->prepare($sql);
		$query->bindValue('key', $key, PDO::PARAM_STR);
		$query->bindValue('id', $id, PDO::PARAM_STR);
		$query->bindValue('user_id', $userId, PDO::PARAM_STR);
		$ok = $query->execute();

		if ($ok) {
			$user->initialise();
		}

		return $ok;

	}

}
