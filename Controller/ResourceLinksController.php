<?php
App::uses('LtiAppController', 'Lti.Controller');
App::import('Vendor', 'Lti.OAuth', ['file' => 'OAuth.php']);

class ResourceLinksController extends LtiAppController {
	public $components = [];
	public $scaffold = 'admin';

}
