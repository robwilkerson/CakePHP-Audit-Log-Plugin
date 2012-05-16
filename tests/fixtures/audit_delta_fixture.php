<?php

class AuditDeltaFixture extends CakeTestFixture {
  public $name = 'AuditDelta';

  public $fields = array(
    'id'            => array( 'type' => 'string', 'length' => 36, 'null' => false ),
    'audit_id'      => array( 'type' => 'string', 'length' => 36, 'null' => false ),
    'property_name' => array( 'type' => 'string', 'length' => 255, 'null' => false ),
    'old_value'     => array( 'type' => 'string', 'length' => 255 ),
    'new_value'     => array( 'type' => 'string', 'length' => 255 ),
  );

  /**
   * records property
   *
   * @var array
   * @access public
   */
  public $records = array();
}
