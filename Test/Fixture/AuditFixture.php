<?php

/**
 * Audit fixtures
 */
class AuditFixture extends CakeTestFixture {

/**
 * Name of the fixture
 *
 * @var string
 */
	public $name = 'Audit';

/**
 * The fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'event' => array('type' => 'string', 'length' => 255, 'null' => false),
		'model' => array('type' => 'string', 'length' => 255, 'null' => false),
		'entity_id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'json_object' => array('type' => 'text', 'null' => false),
		'description' => array('type' => 'text'),
		'source_id' => array('type' => 'string', 'length' => 255),
		'created' => array('type' => 'datetime'),
	);

/**
 * The records
 *
 * @var array
 */
	public $records = array();
}
