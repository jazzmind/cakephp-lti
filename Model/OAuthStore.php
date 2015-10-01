<?php
App::uses('LtiAppModel', 'Lti.Model');
App::uses('Provider', 'Lti.Model');
App::uses('Consumer', 'Lti.Model');
App::uses('Nonce', 'Lti.Model');
App::import('Vendor', 'Lti.OAuth', ['file' => 'OAuth.php']);

class OAuthStore extends LtiAppModel {
	public $useTable = false;

/**
 * @var LTI_Tool_Provider Tool Provider object.
 */
	public $Consumer = NULL;
	public $Provider = NULL;
	public $Nonce = NULL;

/**
 * Class constructor.
 *
 * @param LTI_Tool_Provider $tool_provider Tool_Provider object
 */
	public function __construct($provider=null, $consumer=null) {

		$this->Consumer = ($consumer) ?: new Consumer();
		$this->Provider = ($provider) ?: new Provider();

	}

/**
 * Create an OAuthConsumer object for the tool consumer.
 *
 * @param string $consumer_key Consumer key value
 *
 * @return OAuthConsumer OAuthConsumer object
 */
	function lookup_consumer($consumer_key) {

		$secret = $this->Consumer->field('secret', ['consumer_key' => $consumer_key]);
		if (empty($secret)) {
			// use default value
			$secret = $this->Consumer->secret;
		}
		return new OAuthConsumer($consumer_key, $secret);

	}

/**
 * Create an OAuthToken object for the tool consumer.
 *
 * @param string $consumer   OAuthConsumer object
 * @param string $token_type Token type
 * @param string $token      Token value
 *
 * @return OAuthToken OAuthToken object
 */
	function lookup_token($consumer, $token_type, $token) {

		return new OAuthToken($consumer->key, '');

	}

/**
 * Lookup nonce value for the tool consumer.
 *
 * @param OAuthConsumer $consumer  OAuthConsumer object
 * @param string        $token     Token value
 * @param string        $value     Nonce value
 * @param string        $timestamp Date/time of request
 *
 * @return boolean True if the nonce value already exists
 */
	function lookup_nonce($consumer, $token, $value, $timestamp) {

		$nonce_expiry_min = 30;
		$this->Nonce = new Nonce();
		$this->Nonce->contain();
		$this->Nonce->deleteAll(['expires < now()']);
		$nonce = $this->Nonce->find('first', [
			'conditions' => [
				'consumer_key' => $consumer->key
			]
		]);
		if (empty($nonce)) {
			$data = [
				'consumer_key' => $consumer->key,
				'value' => $value,
				'expires' => date('c', time() + ($nonce_expiry_min * 60)),
			];
			$this->Nonce->create();
			if (!$this->Nonce->save($data)) {
				return $this->Provider->reason = 'Invalid nonce.';
			}
			return false;
		}
		return true;

	}

/**
 * Get new request token.
 *
 * @param OAuthConsumer $consumer  OAuthConsumer object
 * @param string        $callback  Callback URL
 *
 * @return string Null value
 */
	function new_request_token($consumer, $callback = NULL) {

		return NULL;

	}

/**
 * Get new access token.
 *
 * @param string        $token     Token value
 * @param OAuthConsumer $consumer  OAuthConsumer object
 * @param string        $verifier  Verification code
 *
 * @return string Null value
 */
	function new_access_token($token, $consumer, $verifier = NULL) {

		return NULL;

	}

}
