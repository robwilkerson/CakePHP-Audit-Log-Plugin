<?php
namespace AuditLog\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Records changes made to an object during save operations.
 */
class AuditableBehavior extends Behavior
{
    /**
     * A copy of the object as it existed prior to the save. We're going
     * to store this off so we can calculate the deltas after save.
     *
     * @var \Cake\ORM\Table
     */
    protected $_original;


    /**
     * Table instance
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;


    public function __construct(Table $table, array $config)
    {
        parent::__construct($table, $config);
        $this->_table = $table;
    }

    /**
     * @param Event $event
     * @param EntityInterface $entity
     */
    public function afterSafe(Event $event, EntityInterface $entity)
    {

    }

    /**
     * @param Event $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {

    }
}
