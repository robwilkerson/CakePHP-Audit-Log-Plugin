<?php

/**
 * Audit Delta fixtures
 */
class AuditDeltaFixture extends CakeTestFixture {

/**
 * Name of the fixture
 *
 * @var string
 */
	public $name = 'AuditDelta';

/**
 * The fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'audit_id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'property_name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'old_value' => array('type' => 'string', 'length' => 255),
		'new_value' => array('type' => 'string', 'length' => 255),
	);

/**
 * The records
 *
 * @var array
 */
	public $records = array();
}
