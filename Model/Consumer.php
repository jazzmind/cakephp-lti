<?php
App::uses('LtiAppModel', 'Lti.Model');
App::uses('Provider', 'Lti.Model');

class Consumer extends LtiAppModel {

	public $actsAs = ['Containable'];
	public $primaryKey = 'consumer_key';


	public $hasMany = [
		'Nonces' => [
			'className' => 'Lti.Nonce',
			'dependent' => true,
			'foreignKey' => 'consumer_key'
		],
		'ResourceLink' => [
			'className' => 'Lti.ResourceLink',
			'dependent' => true,
			'foreignKey' => 'consumer_key'
		],
		'ShareKey' => [
			'className' => 'Lti.ShareKey',
			'dependent' => true,
			'foreignKey' => 'primary_consumer_key'
		],
		'LTIUser' => [
			'className' => 'Lti.LTIUser',
			'dependent' => true,
			'foreignKey' => 'consumer_key'
		]
	];

/**
 * @var string Local name of tool consumer.
 */
	public $name = NULL;
/**
 * @var string Shared secret.
 */
	public $secret = NULL;
/**
 * @var string LTI version (as reported by last tool consumer connection).
 */
	public $lti_version = NULL;
/**
 * @var string Name of tool consumer (as reported by last tool consumer connection).
 */
	public $consumer_name = NULL;
/**
 * @var string Tool consumer version (as reported by last tool consumer connection).
 */
	public $consumer_version = NULL;
/**
 * @var string Tool consumer GUID (as reported by first tool consumer connection).
 */
	public $consumer_guid = NULL;
/**
 * @var string Optional CSS path (as reported by last tool consumer connection).
 */
	public $css_path = NULL;
/**
 * @var boolean Whether the tool consumer instance is protected by matching the consumer_guid value in incoming requests.
 */
	public $protected = FALSE;
/**
 * @var boolean Whether the tool consumer instance is enabled to accept incoming connection requests.
 */
	public $enabled = FALSE;
/**
 * @var object Date/time from which the the tool consumer instance is enabled to accept incoming connection requests.
 */
	public $enable_from = NULL;
/**
 * @var object Date/time until which the tool consumer instance is enabled to accept incoming connection requests.
 */
	public $enable_until = NULL;
/**
 * @var object Date of last connection from this tool consumer.
 */
	public $last_access = NULL;
/**
 * @var int Default scope to use when generating an Id value for a user.
 */
	public $id_scope = Provider::ID_SCOPE_ID_ONLY;
/**
 * @var string Default email address (or email domain) to use when no email address is provided for a user.
 */
	public $defaultEmail = '';
/**
 * @var object Date/time when the object was created.
 */
	public $created = NULL;
/**
 * @var object Date/time when the object was last modified.
 */
	public $modified = NULL;

/**
 * @var string Consumer key value.
 */
	public $consumer_key = NULL;


/**
 * Class constructor.
 *
 * @param string  $key             Consumer key
 * @param mixed   $data_connector  String containing table name prefix, or database connection object, or array containing one or both values (optional, default is MySQL with an empty table name prefix)
 * @param boolean $autoEnable      true if the tool consumers is to be enabled automatically (optional, default is false)
 */
// 	public function __construct($key = NULL, $data_connector = '', $autoEnable = FALSE) {

// ///		$this->data_connector = LTI_Data_Connector::getDataConnector($data_connector);
// 		if (!empty($key)) {
// 			$this->load($key, $autoEnable);
// 		} else {
// //			$this->secret = LTI_Data_Connector::getRandomString(32);
// 		}

// 	}


	public function afterFind($results, $primary = false) {
		parent::afterFind($results, $primary);
		if (!$primary or empty($results) or !isset($results[0]['Consumer']) or count($results) > 1) {
			return $results;
		}
		foreach ($results[0]['Consumer'] as $key => $value) {
			// set each result key as an object variable
			$this->{$key} = $value;
		}
		return $results;
	}


/**
 * Initialise the tool consumer.
 */
	// public function initialise() {

	// 	$this->consumer_key = NULL;
	// 	$this->name = NULL;
	// 	$this->secret = NULL;
	// 	$this->lti_version = NULL;
	// 	$this->consumer_name = NULL;
	// 	$this->consumer_version = NULL;
	// 	$this->consumer_guid = NULL;
	// 	$this->css_path = NULL;
	// 	$this->protected = FALSE;
	// 	$this->enabled = FALSE;
	// 	$this->enable_from = NULL;
	// 	$this->enable_until = NULL;
	// 	$this->last_access = NULL;
	// 	$this->id_scope = Provider::ID_SCOPE_ID_ONLY;
	// 	$this->defaultEmail = '';
	// 	$this->created = NULL;
	// 	$this->modified = NULL;

	// }


###
###  LTI_Tool_Consumer methods
###

###
#    Load the tool consumer from the database
###
  // public function Tool_Consumer_load($consumer) {

  //   $sql = 'SELECT name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, modified ' .
  //          'FROM ' .$this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
  //          'WHERE consumer_key = :key';
  //   $query = $this->db->prepare($sql);
  //   $key = $consumer->getKey();
  //   $query->bindValue('key', $key, PDO::PARAM_STR);
  //   $ok = $query->execute();

  //   if ($ok) {
  //     $row = $query->fetch(PDO::FETCH_ASSOC);
  //     $ok = ($row !== FALSE);
  //   }

  //   if ($ok) {
  //     $row = array_change_key_case($row);
  //     $consumer->name = $row['name'];
  //     $consumer->secret = $row['secret'];;
  //     $consumer->lti_version = $row['lti_version'];
  //     $consumer->consumer_name = $row['consumer_name'];
  //     $consumer->consumer_version = $row['consumer_version'];
  //     $consumer->consumer_guid = $row['consumer_guid'];
  //     $consumer->css_path = $row['css_path'];
  //     $consumer->protected = ($row['protected'] == 1);
  //     $consumer->enabled = ($row['enabled'] == 1);
  //     $consumer->enable_from = NULL;
  //     if (!is_null($row['enable_from'])) {
  //       $consumer->enable_from = strtotime($row['enable_from']);
  //     }
  //     $consumer->enable_until = NULL;
  //     if (!is_null($row['enable_until'])) {
  //       $consumer->enable_until = strtotime($row['enable_until']);
  //     }
  //     $consumer->last_access = NULL;
  //     if (!is_null($row['last_access'])) {
  //       $consumer->last_access = strtotime($row['last_access']);
  //     }
  //     $consumer->created = strtotime($row['created']);
  //     $consumer->modified = strtotime($row['modified']);
  //   }

  //   return $ok;

  // }

###
#    Save the tool consumer to the database
###
  // public function Tool_Consumer_save($consumer) {

  //   if ($consumer->protected) {
  //     $protected = 1;
  //   } else {
  //     $protected = 0;
  //   }
  //   if ($consumer->enabled) {
  //     $enabled = 1;
  //   } else {
  //     $enabled = 0;
  //   }
  //   $time = time();
  //   $now = date("{$this->date_format} {$this->time_format}", $time);
  //   $from = NULL;
  //   if (!is_null($consumer->enable_from)) {
  //     $from = date("{$this->date_format} {$this->time_format}", $consumer->enable_from);
  //   }
  //   $until = NULL;
  //   if (!is_null($consumer->enable_until)) {
  //     $until = date("{$this->date_format} {$this->time_format}", $consumer->enable_until);
  //   }
  //   $last = NULL;
  //   if (!is_null($consumer->last_access)) {
  //     $last = date($this->date_format, $consumer->last_access);
  //   }
  //   $key = $consumer->getKey();
  //   if (is_null($consumer->created)) {
  //     $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
  //            '(consumer_key, name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, modified) ' .
  //            'VALUES (:key, :name, :secret, :lti_version, :consumer_name, :consumer_version, :consumer_guid, :css_path, ' .
  //            ':protected, :enabled, :enable_from, :enable_until, :last_access, :created, :modified)';
  //     $query = $this->db->prepare($sql);
  //     $query->bindValue('key', $key, PDO::PARAM_STR);
  //     $query->bindValue('name', $consumer->name, PDO::PARAM_STR);
  //     $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
  //     $query->bindValue('lti_version', $consumer->lti_version, PDO::PARAM_STR);
  //     $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
  //     $query->bindValue('consumer_version', $consumer->consumer_version, PDO::PARAM_STR);
  //     $query->bindValue('consumer_guid', $consumer->consumer_guid, PDO::PARAM_STR);
  //     $query->bindValue('css_path', $consumer->css_path, PDO::PARAM_STR);
  //     $query->bindValue('protected', $protected, PDO::PARAM_INT);
  //     $query->bindValue('enabled', $enabled, PDO::PARAM_INT);
  //     $query->bindValue('enable_from', $from, PDO::PARAM_STR);
  //     $query->bindValue('enable_until', $until, PDO::PARAM_STR);
  //     $query->bindValue('last_access', $last, PDO::PARAM_STR);
  //     $query->bindValue('created', $now, PDO::PARAM_STR);
  //     $query->bindValue('modified', $now, PDO::PARAM_STR);
  //   } else {
  //     $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
  //            'SET name = :name, secret = :secret, lti_version = :lti_version, ' .
  //            'consumer_name = :consumer_name, consumer_version = :consumer_version, consumer_guid = :consumer_guid, css_path = :css_path, ' .
  //            'protected = :protected, enabled = :enabled, enable_from = :enable_from, enable_until = :enable_until, last_access = :last_access, modified = :modified ' .
  //            'WHERE consumer_key = :key';
  //     $query = $this->db->prepare($sql);
  //     $query->bindValue('key', $key, PDO::PARAM_STR);
  //     $query->bindValue('name', $consumer->name, PDO::PARAM_STR);
  //     $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
  //     $query->bindValue('lti_version', $consumer->lti_version, PDO::PARAM_STR);
  //     $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
  //     $query->bindValue('consumer_version', $consumer->consumer_version, PDO::PARAM_STR);
  //     $query->bindValue('consumer_guid', $consumer->consumer_guid, PDO::PARAM_STR);
  //     $query->bindValue('css_path', $consumer->css_path, PDO::PARAM_STR);
  //     $query->bindValue('protected', $protected, PDO::PARAM_INT);
  //     $query->bindValue('enabled', $enabled, PDO::PARAM_INT);
  //     $query->bindValue('enable_from', $from, PDO::PARAM_STR);
  //     $query->bindValue('enable_until', $until, PDO::PARAM_STR);
  //     $query->bindValue('last_access', $last, PDO::PARAM_STR);
  //     $query->bindValue('modified', $now, PDO::PARAM_STR);
  //   }
  //   $ok = $query->execute();
  //   if ($ok) {
  //     if (is_null($consumer->created)) {
  //       $consumer->created = $time;
  //     }
  //     $consumer->modified = $time;
  //   }

  //   return $ok;

  // }

###
#    Delete the tool consumer from the database
###
//   public function Tool_Consumer_delete($consumer) {

//     $key = $consumer->getKey();
// // Delete any nonce values for this consumer
//     $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $query->execute();

// // Delete any outstanding share keys for resource links for this consumer
//     $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE primary_consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $query->execute();

// // Delete any users in resource links for this consumer
//     $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::USER_TABLE_NAME . ' WHERE consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $query->execute();

// // Update any resource links for which this consumer is acting as a primary resource link
//     $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
//            'SET primary_consumer_key = NULL, primary_context_id = NULL, share_approved = NULL ' .
//            'WHERE primary_consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $query->execute();

// // Delete any resource links for this consumer
//     $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' WHERE consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $query->execute();

// // Delete consumer
//     $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' WHERE consumer_key = :key';
//     $query = $this->db->prepare($sql);
//     $query->bindValue('key', $key, PDO::PARAM_STR);
//     $ok = $query->execute();

//     if ($ok) {
//       $consumer->initialise();
//     }

//     return $ok;

//   }

###
#    Load all tool consumers from the database
###
  // public function Tool_Consumer_list() {

  //   $consumers = array();

  //   $sql = 'SELECT consumer_key, name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, ' .
  //          'protected, enabled, enable_from, enable_until, last_access, created, modified ' .
  //          "FROM {$this->dbTableNamePrefix}" . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
  //          'ORDER BY name';
  //   $query = $this->db->prepare($sql);
  //   $ok = ($query !== FALSE);

  //   if ($ok) {
  //     $ok = $query->execute();
  //   }
  //   if ($ok) {
  //     while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
  //       $row = array_change_key_case($row);
  //       $consumer = new LTI_Tool_Consumer($row['consumer_key'], $this);
  //       $consumer->name = $row['name'];
  //       $consumer->secret = $row['secret'];;
  //       $consumer->lti_version = $row['lti_version'];
  //       $consumer->consumer_name = $row['consumer_name'];
  //       $consumer->consumer_version = $row['consumer_version'];
  //       $consumer->consumer_guid = $row['consumer_guid'];
  //       $consumer->css_path = $row['css_path'];
  //       $consumer->protected = ($row['protected'] == 1);
  //       $consumer->enabled = ($row['enabled'] == 1);
  //       $consumer->enable_from = NULL;
  //       if (!is_null($row['enable_from'])) {
  //         $consumer->enable_from = strtotime($row['enable_from']);
  //       }
  //       $consumer->enable_until = NULL;
  //       if (!is_null($row['enable_until'])) {
  //         $consumer->enable_until = strtotime($row['enable_until']);
  //       }
  //       $consumer->last_access = NULL;
  //       if (!is_null($row['last_access'])) {
  //         $consumer->last_access = strtotime($row['last_access']);
  //       }
  //       $consumer->created = strtotime($row['created']);
  //       $consumer->modified = strtotime($row['modified']);
  //       $consumers[] = $consumer;
  //     }
  //   }

  //   return $consumers;

  // }

}
