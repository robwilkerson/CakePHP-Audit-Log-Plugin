<?php

/**
 * Article fixtures
 */
class ArticleFixture extends CakeTestFixture {

/**
 * Name of the fixture
 *
 * @var string
 */
	public $name = 'Article';

/**
 * The fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false),
		'author_id' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'null' => false),
		'body' => array('type' => 'text'),
		'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
		'ignored_field' => array('type' => 'integer', 'length' => 1, 'default' => 0),
		'created' => array('type' => 'datetime'),
		'updated' => array('type' => 'datetime'),
	);

/**
 * The records
 *
 * @var array
 */
	public $records = array(
		array(
			'user_id' => 1,
			'author_id' => 1,
			'title' => 'First Article',
			'body' => 'First Article Body',
			'published' => 'Y',
			'created' => '2007-03-18 10:39:23',
			'updated' => '2007-03-18 10:41:31'
		),
		array(
			'user_id' => 3,
			'author_id' => 3,
			'title' => 'Second Article',
			'body' => 'Second Article Body',
			'published' => 'Y',
			'created' => '2007-03-18 10:41:23',
			'updated' => '2007-03-18 10:43:31'
		),
		array(
			'user_id' => 1,
			'author_id' => 1,
			'title' => 'Third Article',
			'body' => 'Third Article Body',
			'published' => 'Y',
			'created' => '2007-03-18 10:43:23',
			'updated' => '2007-03-18 10:45:31'
		),
	);
}
