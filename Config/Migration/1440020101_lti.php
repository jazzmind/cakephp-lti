<?php
class Lti extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'Add LTI support';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = [
		'up' => [
			'create_table' => [
				'lti_consumers' => [
					'consumer_key' => ['type' => 'string', 'null' => false, 'length' => 255],
					'name' => ['type' => 'string', 'null' => false, 'length' => 45],
					'secret' => ['type' => 'string', 'null' => false, 'length' => 32],
					'lti_version' => ['type' => 'string', 'null' => true, 'length' => 12],
					'consumer_name' => ['type' => 'string', 'null' => true, 'length' => 255],
					'consumer_version' => ['type' => 'string', 'null' => true, 'length' => 255],
					'consumer_guid' => ['type' => 'string', 'null' => true, 'length' => 255],
					'css_path' => ['type' => 'string', 'null' => true, 'length' => 255],
					'protect' => ['type' => 'boolean', 'null' => false, 'default' => true],
					'enabled' => ['type' => 'boolean', 'null' => false, 'default' => false],
					'enable_from' => ['type' => 'datetime', 'null' => true],
					'enable_until' => ['type' => 'datetime', 'null' => true],
					'last_access' => ['type' => 'date', 'null' => true],
					'created' => ['type' => 'datetime', 'null' => false],
					'modified' => ['type' => 'datetime', 'null' => false],
					'indexes' => [
						'PRIMARY' => ['unique' => true, 'column' => 'consumer_key'],
					],
					'tableParameters' => [],
				],
				'lti_contexts' => [
					'id' => ['type' => 'integer', 'null' => false, 'key' => 'primary'],
					'consumer_key' => ['type' => 'string', 'null' => false, 'length' => 255],
					'context_id' => ['type' => 'string', 'null' => false, 'length' => 255],
					'lti_context_id' => ['type' => 'string', 'null' => true, 'length' => 255],
					'lti_resource_id' => ['type' => 'string', 'null' => true, 'length' => 255],
					'title' => ['type' => 'string', 'null' => false, 'length' => 255],
					'primary_consumer_key' => ['type' => 'string', 'null' => true, 'length' => 255],
					'primary_context_id' => ['type' => 'string', 'null' => true, 'length' => 255],
					'share_approved' => ['type' => 'boolean', 'null' => true],
					'settings' => ['type' => 'text', 'null' => true, 'length' => 1073741824],
					'created' => ['type' => 'datetime', 'null' => false],
					'modified' => ['type' => 'datetime', 'null' => false],
					'indexes' => [
						'PRIMARY' => ['unique' => true, 'column' => 'id'],
						'context_unique' => ['unique' => true, 'column' => ['consumer_key', 'context_id']],
					],
					'tableParameters' => [],
				],
				'lti_users' => [
					'id' => ['type' => 'integer', 'null' => false, 'key' => 'primary' ],
					'consumer_key' => ['type' => 'string', 'null' => false, 'length' => 255],
					'context_id' => ['type' => 'string', 'null' => false, 'length' => 255],
					'user_id' => ['type' => 'string', 'null' => false, 'length' => 255],
					'lti_result_sourcedid' => ['type' => 'string', 'null' => false, 'length' => 255],
					'created' => ['type' => 'datetime', 'null' => false],
					'modified' => ['type' => 'datetime', 'null' => false],
					'indexes' => [
						'PRIMARY' => ['unique' => true, 'column' => 'id'],
						'user_unique' => ['unique' => true, 'column' => ['consumer_key', 'context_id', 'user_id']],
					],
					'tableParameters' => [],
				],

				'lti_nonces' => [
					'id' => ['type' => 'integer', 'null' => false, 'key' => 'primary' ],
					'consumer_key' => ['type' => 'string', 'null' => false, 'length' => 255],
					'value' => ['type' => 'string', 'null' => false, 'length' => 32],
					'expires' => ['type' => 'datetime', 'null' => false],
					'indexes' => [
						'PRIMARY' => ['unique' => true, 'column' => 'id'],
						'nonce_unique' => ['unique' => true, 'column' => ['consumer_key', 'value']],
					],
					'tableParameters' => [],
				],

				'lti_share_keys' => [
					'id' => ['type' => 'integer', 'null' => false, 'key' => 'primary'],
					'primary_consumer_key' => ['type' => 'string', 'null' => false, 'length' => 255],
					'primary_context_id' => ['type' => 'string', 'null' => false, 'length' => 255],
					'auto_approve' => ['type' => 'boolean', 'null' => false],
					'expires' => ['type' => 'datetime', 'null' => false],
					'indexes' => [
						'PRIMARY' => ['unique' => true, 'column' => 'id'],
					],
					'tableParameters' => [],
				],

			],


			// 'create_field' => [
			// 	'core_programs' => [
			// 		'dashboard_content' => ['type' => 'text', 'null' => true, 'length' => 1073741824],
			// 	],
			// ],
		],
		'down' => [
			'drop_table' => [
				'lti_consumers', 'lti_contexts', 'lti_users', 'lti_share_keys', 'lti_nonces'
			]
		],
	];

/**
 * Before migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function after($direction) {
		return true;
	}
}
