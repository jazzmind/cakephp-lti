<?php
App::uses('LTIAppModel', 'LTI.Model');

class ConsumerNonce extends LTIAppModel {

	public $actsAs = ['Containable'];

###
###  LTI_Consumer_Nonce methods
###

###
#    Load the consumer nonce from the database
###
	public function load($nonce) {

// Delete any expired nonce values
		$now = date("{$this->date_format} {$this->time_format}", time());
		$sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE expires <= :now';
		$query = $this->db->prepare($sql);
		$query->bindValue('now', $now, PDO::PARAM_STR);
		$query->execute();

// Load the nonce
		$key = $nonce->getKey();
		$value = $nonce->getValue();
		$sql = 'SELECT value T FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE (consumer_key = :key) AND (value = :value)';
		$query = $this->db->prepare($sql);
		$query->bindValue('key', $key, PDO::PARAM_STR);
		$query->bindValue('value', $value, PDO::PARAM_STR);
		$ok = $query->execute();
		if ($ok) {
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if ($row === FALSE) {
				$ok = FALSE;
			}
		}

		return $ok;

	}

###
#    Save the consumer nonce in the database
###
	public function save($nonce) {

		$key = $nonce->getKey();
		$value = $nonce->getValue();
		$expires = date("{$this->date_format} {$this->time_format}", $nonce->expires);
		$sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' (consumer_key, value, expires) VALUES (:key, :value, :expires)';
		$query = $this->db->prepare($sql);
		$query->bindValue('key', $key, PDO::PARAM_STR);
		$query->bindValue('value', $value, PDO::PARAM_STR);
		$query->bindValue('expires', $expires, PDO::PARAM_STR);
		$ok = $query->execute();

		return $ok;

	}
}
