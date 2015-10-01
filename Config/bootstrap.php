<?php
//
// You have two options:
// the first is that you can add methods to ProvidersController and put these method names in as strings, e.g.
// launch => 'doLaunch'
// Alternatively, you can specify a model and method in an array, and it will load the model and call the method,
// passing the ProviderController object in as context
Configure::write('LTI.callbackHandler', [
	'launch' => ['model' => 'LtiResource', 'method' => 'lti_launch'],
	'dashboard' => '',
	'error' => '',
	'configure' => '',
	'content-item' => '',
]);
