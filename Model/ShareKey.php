<?php
App::uses('LtiAppModel', 'Lti.Model');

class ShareKey extends LtiAppModel {

	public $actsAs = ['Containable'];

	public $belongsTo = [
		'Consumer' => [
			'className' => 'Lti.Consumer',
			'dependent' => true,
			'foreignKey' => 'primary_consumer_key'
		],
		'Context' => [
			'className' => 'Lti.Context',
			'dependent' => true,
			'foreignKey' => 'primary_context_id'
		],
	];

###
###  LTI_Resource_Link_Share_Key methods
###

###
#    Load the resource link share key from the database
###
	public function load() {

// Clear expired share keys
		$this->deleteAll(['expires <= now()']);
		// $now = date("{$this->date_format} {$this->time_format}", time());
		// $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE expires <= :now';
		// $query = $this->db->prepare($sql);
		// $query->bindValue('now', $now, PDO::PARAM_STR);
		// $query->execute();

// Load share key
		// $id = $share_key->getId();
		// $sql = 'SELECT share_key_id, primary_consumer_key, primary_context_id, auto_approve, expires ' .
		// 			 'FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
		// 			 'WHERE share_key_id = :id';
		// $query = $this->db->prepare($sql);
		// $query->bindValue('id', $id, PDO::PARAM_STR);
		// $ok = $query->execute();
		// if ($ok) {
		// 	$row = $query->fetch(PDO::FETCH_ASSOC);
		// 	$ok = ($row !== FALSE);
		// }
		$this->data = $this->read();
		if (!empty($this->data)) {
			foreach ($this->data['ShareKey'] as $k => $v) {
				$this->{$k} = $v;
			}
			return true;
		}
		return false;

	}

###
#    Save the resource link share key to the database
###
	// public function save($data=null) {

	// 	if (!empty($data)) {
	// 		$this->data = $data;
	// 	}

	// 	return $this->save($this->data);
	// 	// $expires = date("{$this->date_format} {$this->time_format}", $share_key->expires);
	// 	// $id = $share_key->getId();
	// 	// $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
	// 	// 			 '(share_key_id, primary_consumer_key, primary_context_id, auto_approve, expires) ' .
	// 	// 			 'VALUES (:id, :primary_consumer_key, :primary_context_id, :approve, :expires)';
	// 	// $query = $this->db->prepare($sql);
	// 	// $query->bindValue('id', $id, PDO::PARAM_STR);
	// 	// $query->bindValue('primary_consumer_key', $share_key->primary_consumer_key, PDO::PARAM_STR);
	// 	// $query->bindValue('primary_context_id', $share_key->primary_resource_link_id, PDO::PARAM_STR);
	// 	// $query->bindValue('approve', $approve, PDO::PARAM_INT);
	// 	// $query->bindValue('expires', $expires, PDO::PARAM_STR);

	// 	//return $query->execute();

	// }

###
#    Delete the resource link share key from the database
###
	// public function delete() {

	// 	return $this->delete();
	// 	// $id = $share_key->getId();
	// 	// $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE share_key_id = :id';
	// 	// $query = $this->db->prepare($sql);
	// 	// $query->bindValue('id', $id, PDO::PARAM_STR);
	// 	// $ok = $query->execute();
	// 	// if ($ok) {
	// 	// 	$share_key->initialise();
	// 	// }

	// 	// return $ok;

	// }
}
