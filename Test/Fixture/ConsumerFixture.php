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
            "consumer_key" => "5e420698-5988-422d-bb7d-5e4bac140005",
            "name" => "name",
            "secret" => "D0EA-A61B-369E",
            "lti_version" => "LTI-1p0",
            "consumer_name" => "consumer_name",
            "consumer_version" => "1",
            "consumer_guid" => "guid1",
            "css_path" => null,
            "protect" => true,
            "enabled" => true,
            "enable_from" => null,
            "enable_until" => null,
            "timeline_id"  => 1
        ],
        [
            "id" => 2,
            "consumer_key" => "5e426670-7268-4ba1-b15a-7cdfac140005",
            "name" => "name",
            "secret" => "5610-C506-A3DE",
            "lti_version" => "LTI-1p0",
            "consumer_name" => "consumer_name",
            "consumer_version" => "1",
            "consumer_guid" => "guid2",
            "css_path" => null,
            "protect" => true,
            "enabled" => true,
            "enable_from" => null,
            "enable_until" => null,
            "timeline_id"  => 2
        ]
    ];
}
