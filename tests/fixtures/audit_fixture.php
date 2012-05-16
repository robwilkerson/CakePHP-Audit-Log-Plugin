<?php

class AuditFixture extends CakeTestFixture {
  public $name = 'Audit';

  public $fields = array(
    'id'          => array( 'type' => 'string', 'length' => 36, 'null' => false ),
    'event'       => array( 'type' => 'string', 'length' => 255, 'null' => false ),
    'model'       => array( 'type' => 'string', 'length' => 255, 'null' => false ),
    'entity_id'   => array( 'type' => 'string', 'length' => 36, 'null' => false ),
    'json_object' => array( 'type' => 'text', 'null' => false ),
    'description' => array( 'type' => 'text' ),
    'source_id'   => array( 'type' => 'string', 'length' => 255 ),
    'created'     => array( 'type' => 'datetime' ),
  );

  /**
   * records property
   *
   * @var array
   * @access public
   */
  public $records = array();
}
