<?php
/**
 * LtiUserFixture
 *
 */
class LtiUserFixture extends CakeTestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $useDbConfig = 'test';
	public $import = 'Lti.LtiUser';


/**
 * Records
 *
 * @var array
 */
	public $records = [
        [
            "id" => 1,
            "consumer_key" => "testkey",
            "context_id" => "context",
            "user_id" => "USERID1001",
            "lis_result_sourcedid" => "result_sourcedid",
            "lis_person_sourcedid" => "school.edu:USERID1001",
        ]
    ];
}
