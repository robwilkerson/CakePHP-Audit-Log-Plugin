<?php
namespace AuditLog\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;

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

    /**
     * The request_id, a unique ID generated once per request to allow multiple record changes to be grouped by request
     */
    private static $_request_id = null;

    private function request_id()
    {
        if (empty(self::$_request_id)) {
            self::$_request_id = Text::uuid();
        }
        return self::$_request_id;
    }

    public function __construct(Table $table, array $config)
    {
        parent::__construct($table, $config);
        $this->_table = $table;
        $this->_ignore_properties = $config;
    }

    /**
     * @param Event $event
     * @param EntityInterface $entity
     */
    public function afterSafe(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        //before change the data
        $arrOldData = $entity->extractOriginal($entity->visibleProperties());
        //updated fields array object
        $arrUpdatedProperties = $entity->extractOriginalChanged($entity->visibleProperties());
        //updated data object
        $arrUpdatedData = $entity->jsonSerialize();
        //audit data
        $arrData = array(
            'id' => self::request_id(),
            'event' => ($entity->isNew()) ? 'CREATE' : 'EDIT',
            'model' => $event->subject()->alias(),
            'entity_id' => $entity->id,
            'request_id' => self::request_id(),
            'json_object' => json_encode($arrUpdatedData),
            'source_id' => isset($source['id']) ? $source['id'] : null,
            'description' => isset($source['description']) ? $source['description'] : null
        );
        //saving audit record
        $auditTable = TableRegistry::get('Audits');
        $audit = $auditTable->newEntity($arrData);
        $auditTable->save($audit);

        $auditDeltaTable = TableRegistry::get('AuditDeltas');
        foreach ($arrUpdatedProperties as $sPropertyName => $sValue) {
            //ignore the fields
            if (in_array($sPropertyName, $this->_ignore_properties)) {
                continue;
            }
            $delta = array(
                'id' => Text::uuid(),
                'audit_id' => $audit->id,
                'property_name' => $sPropertyName,
                'old_value' => $sValue,
                'new_value' => $entity->$sPropertyName
            );
            $auditDeltas = $auditDeltaTable->newEntity($delta);
            $auditDeltaTable->save($auditDeltas);
        }
    }

    /**
     * Currently not in use
     *
     * @param Event $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     * @return true
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        return true;
    }
}
