<?php
App::uses('Model', 'Model');
App::uses('LTI_Tool_Provider', 'LTI.Lib');

class LTIAppModel extends AppModel
{
	public $tablePrefix = 'lti_';
	public $recursive = -1;


}
