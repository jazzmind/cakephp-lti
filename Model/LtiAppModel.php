<?php
App::uses('Model', 'Model');
App::uses('LTI_Tool_Provider', 'Lti.Lib');

class LtiAppModel extends AppModel
{
	public $tablePrefix = 'lti_';
	public $recursive = -1;

	// this will set model properties based on the schema and data returned from a find('first')
	// or any find with just 1 result. E.g. $this->data[LtiUser][id] will become $this->id
	public function afterFind($results, $primary = false) {
		parent::afterFind($results, $primary);
		if (!$primary or empty($results)) {
			return $results;
		}

		$set = [];
		if (isset($results[0][$this->alias]) and count($results) == 1) {
			$set = &$results[0][$this->alias];
		} else if (isset($results[$this->alias])) {
			$set = &$results[$this->alias];
		}

		foreach ($set as $key => $value) {
			// set each result key as an object variable
			$this->{$key} = $value;
		}
		return $results;
	}


	// public function beforeSave($options = []) {

	// 	if (empty($this->_schema)) {
	// 		return true;
	// 	}
	// 	foreach ($this->_schema as $var => $extra) {
	// 		if(!empty($this->{$var})) {
	// 			$this->data[$this->alias][$var] = $this->{$var};
	// 		}
	// 	}
	// 	return parent::beforeSave($options);
	// }
}
