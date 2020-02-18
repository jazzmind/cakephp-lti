<?php
App::uses('ProvidersController', 'Lti.Controller');

/**
 * ProvidersController Test Case
 *
 * IMPORTANT, if you are running the tests from a browser make sure you run it from the same URL as set up in PRACTERA_URL environmental variable
 * It will not work from an alternate domain because of the launch URL.
 */
class ProvidersControllerTest extends ControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.cache_record',
		'app.enrolment',
		'app.license',
		'app.user',
		'app.user_profile',
		'app.user_action_log',
		'app.user_auth',
		'app.user_auth_token',
		'app.session',
		'app.experience',
		'app.program',
		'app.institution',
		'plugin.project.timeline',
		'plugin.lti.consumer',
		'plugin.lti.lti_user',
		// 'plugin.lti.nonce',
		'plugin.lti.resource_link',
		// 'plugin.lti.share_key',
	);

	public function testRequest() {
		$keys = [
			"5e420698-5988-422d-bb7d-5e4bac14eeee", // wrong
			"5e420698-5988-422d-bb7d-5e4bac140005", // correct
			"5e426670-7268-4ba1-b15a-7cdfac140005" // correct
		];
		$secrets = [
			"D0EA-A61B-369A",	// wrong
			"D0EA-A61B-369E",	// correct
			"5610-C506-A3DE"	// correct
		];
		$guids = [
			'guid0',	// wrong
			'guid1',	// correct
			'guid2'		// correct
		];
		$testCases = [
			[
				'key' => 1,
				'secret' => 1,
				'guid' => 1,
				'messageType' => 'APITokenRequest',
				'correct' => true,		// all info are correct
				'newUser' => true, 		// it will create a new user
				'newEnrolment' => true // it will create a new enrolment
			],
			[
				'key' => 1,
				'secret' => 1,
				'guid' => 1,
				'messageType' => 'basic-lti-launch-request',
				'correct' => true,		// all info are correct
				'newUser' => false,
				'newEnrolment' => false
			],
			[
				'key' => 2,
				'secret' => 2,
				'guid' => 2,
				'messageType' => 'basic-lti-launch-request',
				'correct' => true,		// all info are correct
				'newUser' => false,
				'newEnrolment' => true,
				'returnURL' => ''
			],
			[
				'key' => 2,
				'secret' => 2,
				'guid' => 2,
				'messageType' => 'basic-lti-launch-request',
				'correct' => true,		// all info are correct
				'newUser' => false,
				'newEnrolment' => false,
				'returnURL' => 'http://google.com/'
			],
			[
				'key' => 0,	// key incorrect
				'secret' => 1,
				'guid' => 1,
				'messageType' => 'basic-lti-launch-request',
				'correct' => false,
				'newUser' => false,
				'newEnrolment' => false
			],
			[
				'key' => 1,
				'secret' => 0,	// secret incorrect
				'guid' => 1,
				'messageType' => 'basic-lti-launch-request',
				'correct' => false,
				'newUser' => false,
				'newEnrolment' => false
			],
			[
				'key' => 1,
				'secret' => 1,
				'guid' => 0,	// guid incorrect
				'messageType' => 'basic-lti-launch-request',
				'correct' => false,
				'newUser' => false,
				'newEnrolment' => false
			]
		];
		$user = [
			'id' => 3,
			'firstname' => "TestFirst",
			'lastname' => "TestLast",
			'fullname' => "TestFirst TestLast",
			'email' => "lti_test@practera.com",
			'resource_link_id' => "resource_link_id",
		];

		foreach ($testCases as $testCase) {
			$this->User = ClassRegistry::init("User");
			$this->Enrolment = ClassRegistry::init("Enrolment");
			$userCountBefore = $this->User->find('count');
			$enrolmentCountBefore = $this->Enrolment->find('count');
			$options = [
				'key' => $keys[$testCase['key']],
				'secret' => $secrets[$testCase['secret']],
				'guid' => $guids[$testCase['guid']],
				'messageType' => $testCase['messageType'],
			];
			if(isset($testCase['returnURL'])) {
				$options['returnURL'] = $testCase['returnURL'];
			}

			$data = $this->_dataForLti($user, $options);
			$_SERVER['QUERY_STRING'] = http_build_query($data);
			$result = $this->testAction('/lti/providers/request', [
				'data' => $data,
				'method' => 'post'
			]);
			$userCountAfter = $this->User->find('count');
			$enrolmentCountAfter = $this->Enrolment->find('count');
			if (!$testCase['correct']) {
				$this->assertEmpty($result);
				$this->assertFalse(isset($this->headers['Location']));
				continue;
			}

			if ($testCase['messageType'] == 'APITokenRequest') {
				$this->assertContains('"jwt":', $result);
			} else {
				$this->assertContains('do=secure', $this->headers['Location']);
			}
			if ($testCase['newUser']) {
				$this->assertEquals($userCountBefore + 1, $userCountAfter);
			} else {
				$this->assertEquals($userCountBefore, $userCountAfter);
			}
			if ($testCase['newEnrolment']) {
				$this->assertEquals($enrolmentCountBefore + 1, $enrolmentCountAfter);
			} else {
				$this->assertEquals($enrolmentCountBefore, $enrolmentCountAfter);
			}

			if (!empty($testCase['returnURL']) == 'APITokenRequest') {
				$this->assertContains('return_url=', $this->headers['Location']);
			}

		}

	}

	protected function _dataForLti($user, $options) {
		$config = Configure::read('App');
		if (php_sapi_name() == "cli") {
			$launch_url = rtrim($config['fullBaseUrl'], '/') . '/app/Console/lti/providers/request';
		} else {
			$launch_url = rtrim($config['fullBaseUrl'], '/') . '/lti/providers/request';
		}

		$launch_data = [
			"roles" => "Learner",	#only value that should be provided for now

			# https://www.imsglobal.org/specs/ltiv1p0/implementation-guide
			"tool_consumer_instance_guid" => $options['guid'], # this is what you should use, if you do not already have one just keep this, if you do please send it to us and we will change it in our system.
			"tool_consumer_instance_description" => "Deakin University",
			"lis_result_sourcedid" => 'Deakin University Local',

			# uni details, see this https://www.imsglobal.org/basic-overview-how-lti-works for explanation of the values.
			"context_id" => "skillbuilder" . $user["resource_link_id"], # this uniquely identifies the context. It should probably be uber related to be able to reference it in the future
			"context_title" => "SkillBuilder" . $user["resource_link_id"], # It should probably be uber related to be able to reference it in the future

			# user specific details used to register/login them in out system
			"resource_link_id" => $user["resource_link_id"],
			"lis_person_name_full" => $user["fullname"],
			"lis_person_name_family" => $user["firstname"],
			"lis_person_name_given" => $user["lastname"],
			"lis_person_contact_email_primary" => $user["email"],
			"lis_person_sourcedid" => "school.edu:" .$user["id"]
		];

		if(isset($options['returnURL'])) {
			$launch_data['launch_presentation_return_url'] = $options['returnURL'];
		}

		#
		# END OF CONFIGURATION SECTION
		# ------------------------------

		$now = new DateTime();

		$launch_data["lti_version"] = "LTI-1p0";
		$launch_data["lti_message_type"] = $options['messageType'];
		# Basic LTI uses OAuth to sign requests
		# OAuth Core 1.0 spec: http://oauth.net/core/1.0/

		$launch_data["oauth_callback"] = "about:blank";
		$launch_data["oauth_consumer_key"] = $options['key'];
		$launch_data["oauth_version"] = "1.0";
		$launch_data["oauth_nonce"] = uniqid('', true);
		$launch_data["oauth_timestamp"] = $now->getTimestamp();

		$launch_data["oauth_signature_method"] = "HMAC-SHA1";

		# In OAuth, request parameters must be sorted by name
		$launch_data_keys = array_keys($launch_data);
		sort($launch_data_keys);

		$launch_params = array();
		foreach ($launch_data_keys as $key) {
			array_push($launch_params, $key . "=" . rawurlencode($launch_data[$key]));
		}

		$base_string = "POST&" . urlencode($launch_url) . "&" . rawurlencode(implode("&", $launch_params));

		$secret = urlencode($options['secret']) . "&";
		$launch_data["oauth_signature"] = base64_encode(hash_hmac("sha1", $base_string, $secret, true));
		return $launch_data;
	}

}
?>
