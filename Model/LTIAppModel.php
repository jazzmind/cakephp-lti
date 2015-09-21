<?php
App::uses('Model', 'Model');
App::uses('LTI_Tool_Provider', 'Lti.Lib');

class LtiAppModel extends AppModel
{
	public $tablePrefix = 'lti_';
	public $recursive = -1;


}
