<?php

/**
 * Author fixtures
 */
class AuthorFixture extends CakeTestFixture {

/**
 * The name of the fixture
 *
 * @var string
 */
	public $name = 'Author';

/**
 * The fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'first_name' => array('type' => 'string', 'null' => false),
		'last_name' => array('type' => 'string', 'null' => false),
		'created' => array('type' => 'datetime'),
		'updated' => array('type' => 'datetime'),
	);

/**
 * The records
 *
 * @var array
 */
	public $records = array();
}
