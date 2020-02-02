<?php
/**
 * ConsumerFixture
 *
 */
class ConsumerFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $useDbConfig = 'test';
	public $import = 'Lti.Consumer';


/**
 * Records
 *
 * @var array
 */
	public $records = [
        [
            "id" => 1,
            "consumer_key" => "testkey",
            "name" => "name",
            "secret" => "secret",
            "lti_version" => "LTI-1p0",
            "consumer_name" => "consumer_name",
            "consumer_version" => "1",
            "consumer_guid" => "guid",
            "css_path" => null,
            "protect" => true,
            "enabled" => true,
            "enable_from" => null,
            "enable_until" => null,
            "timeline_id"  => 1
        ]
    ];
}
