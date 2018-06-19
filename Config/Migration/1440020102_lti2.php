<?php
class Lti2 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'Add LTI support - part 2';

/**
 * Actions to be performed
 * drop lti_resource_links context_id  
 * @var array $migration
 */
	public $migration = [
		'up' => [
			'rename_table' => [
				'lti_contexts' => 'lti_resource_links'
			],
			'create_field' => [
				'lti_users' => [
					'lis_person_sourcedid' => ['type' => 'string', 'null' => false, 'length' => 255],
				],
			],

			'drop_field' => [
				'lti_resource_links' => [
					'context_id',
				],
			],
			'rename_field' => [
				'lti_users' => [
					'lti_result_sourcedid' => 'lis_result_sourcedid'
				],
			],
		],
		'down' => [
			'rename_table' => [
				'lti_resource_links' => 'lti_contexts'
			],
			'drop_field' => [
				'lti_users' => [
					'lis_person_sourcedid',
				],
			],

			'create_field' => [
				'lti_resource_links' => [
					'context_id' => ['type' => 'string', 'null' => false, 'length' => 255],
				],
			],
			'rename_field' => [
				'lti_users' => [
					'lis_result_sourcedid' => 'lti_result_sourcedid'
				],
			],			
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
