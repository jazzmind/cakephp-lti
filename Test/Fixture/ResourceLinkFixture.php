<?php
/**
 * ResourceLinkFixture
 *
 */
class ResourceLinkFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $useDbConfig = 'test';
	public $import = 'Lti.ResourceLink';


/**
 * Records
 *
 * @var array
 */
	public $records = [
		[
			"id" => 1,
			"consumer_key" => "5e420698-5988-422d-bb7d-5e4bac140005",
			"lti_context_id" => "launch-api",
			"lti_resource_id" => "resource_link_id",
			"title" => "API Launch Request",
		],
		[
			"id" => 2,
			"consumer_key" => "5e426670-7268-4ba1-b15a-7cdfac140005",
			"lti_context_id" => "launch-api",
			"lti_resource_id" => "resource_link_id",
			"title" => "API Launch Request",
		]
	];
}
