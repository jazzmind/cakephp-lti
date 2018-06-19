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
			'alter_table' => [
				'lti_contexts' => [
					'rename' => ['lti_resource_links']
				],
			],

			'drop_field' => [
				'lti_resource_links' => [
					'context_id',
				],
			],
		],
		'down' => [
			'alter_table' => [
				'lti_resource_links' => [
					'rename' => ['lti_contexts']
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
