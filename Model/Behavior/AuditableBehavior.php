<?php

App::uses('Audit', 'AuditLog.Model');
App::uses('AuditDelta', 'AuditLog.Model');

/**
 * Records changes made to an object during save operations.
 */
class AuditableBehavior extends \ModelBehavior {

/**
 * A copy of the object as it existed prior to the save. We're going
 * to store this off, so we can calculate the deltas after save.
 *
 * @var array
 */
	protected $_original = array();

/**
 * The requestId is a unique ID generated once per request to allow multiple record changes to be grouped by request
 *
 * @var string
 */
	protected static $_requestId = null;

/**
 * A copy of the model virtual fields to restore from
 *
 * @var
 */
	protected $_modelVirtualFields;

/**
 * A copy of the model order to restore from
 *
 * @var
 */
	protected $_modelOrder;

/**
 * Initiate behavior for the model using specified settings.
 *
 * Available settings:
 *   - ignore array, optional
 *            An array of property names to be ignored when records
 *            are created in the deltas table.
 *   - habtm  array, optional
 *            An array of models that have a HABTM relationship with
 *            the acting model and whose changes should be monitored
 *            with the model.
 *
 * @param Model $Model The model using the behavior.
 * @param array $settings The settings overrides.
 * @return void
 */
	public function setup(Model $Model, $settings = array()) {
		// Do not act on the AuditLog related models.
		if ($this->_isAuditLogModel($Model)) {
			return;
		}

		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias]['ignore'] = array('created', 'updated', 'modified');
			$this->settings[$Model->alias]['habtm'] = array();
			if (!isset($settings['habtm'])) {
				$this->settings[$Model->alias]['habtm'] = count($Model->hasAndBelongsToMany) > 0
					? array_keys($Model->hasAndBelongsToMany)
					: array();
			}
		}
		if (!is_array($settings)) {
			$settings = array();
		}
		$this->settings[$Model->alias] = array_merge_recursive($this->settings[$Model->alias], $settings);

		// Ensure that no HABTM models, which are already auditable,
		// snuck into the settings array. That would be bad. Same for
		// any model which isn't a HABTM association.
		foreach ($this->settings[$Model->alias]['habtm'] as $index => $modelName) {
			// Note the "===" in the condition. The type check is important,
			// so don't change it just because it may look like a mistake.
			if (!array_key_exists($modelName, $Model->hasAndBelongsToMany)
				|| (is_array($Model->$modelName->actsAs)
					&& array_search('Auditable', $Model->$modelName->actsAs) === true)
			) {
				unset($this->settings[$Model->alias]['habtm'][$index]);
			}
		}
	}

/**
 * Executed before a save operation.
 *
 * @param Model $Model The model using the behavior.
 * @param array $options The options data (unused).
 * @return true Always true.
 */
	public function beforeSave(Model $Model, $options = array()) {
		// Do not act on the AuditLog related models.
		if ($this->_isAuditLogModel($Model)) {
			return true;
		}

		// If we're editing an existing object, save off a copy of
		// the object as it exists before any changes.
		if (!empty($Model->id)) {
			$this->_setOriginalDataForModel($Model, $this->_getModelData($Model));
		}

		return true;
	}

/**
 * Executed before a delete operation.
 *
 * @param Model $Model The model using the behavior.
 * @param bool $cascade Whether to cascade (unused).
 * @return true Always true.
 */
	public function beforeDelete(Model $Model, $cascade = true) {
		// Do not act on the AuditLog related models.
		if ($this->_isAuditLogModel($Model)) {
			return true;
		}

		// Skip, if no ID to delete was given.
		if ($Model->id === false) {
			return true;
		}

		$this->_unsetVirtualFieldsTemporarily($Model);

		$original = $Model->find(
			'first',
			array(
				'contain' => false,
				'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
			)
		);
		$this->_setOriginalDataForModel($Model, $original[$Model->alias]);

		$this->_restoreVirtualFields($Model);

		return true;
	}

/**
 * Executed after a save operation completes.
 *
 * @param Model $Model The model that is used for the save operation.
 * @param bool $created True, if the save operation was an insertion, false otherwise.
 * @param array $options The options data (unused).
 * @return true Always true.
 */
	public function afterSave(Model $Model, $created, $options = array()) {
		// Do not act on the AuditLog related models.
		if ($this->_isAuditLogModel($Model)) {
			return true;
		}

		$modelData = $this->_getModelData($Model);
		if (!$modelData) {
			$this->afterDelete($Model);

			return true;
		}

		$audit[$Model->alias] = $modelData;
		$audit[$Model->alias][$Model->primaryKey] = $Model->id;

		// Create a runtime association with the Audit model
		$Model->bindModel(
			array('hasMany' => array('AuditLog.Audit'))
		);

		// If a currentUser() method exists in the model class (or, of
		// course, in a superclass) the call that method to pull all user
		// data. Assume than an ID field exists.
		$source = array();
		if ($Model->hasMethod('currentUser')) {
			$source = $Model->currentUser();
		} elseif ($Model->hasMethod('current_user')) {
			$source = $Model->current_user();
		}

		$data = array(
			'Audit' => array(
				'event' => $created ? 'CREATE' : 'EDIT',
				'model' => $Model->plugin ? $Model->plugin . '.' . $Model->alias : $Model->alias,
				'entity_id' => $Model->id,
				'request_id' => self::_requestId(),
				'json_object' => json_encode($audit),
				'source_id' => isset($source['id']) ? $source['id'] : null,
				'description' => isset($source['description']) ? $source['description'] : null,
			),
		);

		// We have the audit_logs record, so let's collect the set of
		// records that we'll insert into the audit_log_deltas table.
		$updates = array();
		foreach ($audit[$Model->alias] as $property => $value) {
			$delta = array();

			// Ignore virtual fields (Cake 1.3+) and specified properties.
			if (($Model->hasMethod('isVirtualField') && $Model->isVirtualField($property))
				|| in_array($property, $this->settings[$Model->alias]['ignore'])
			) {
				continue;
			}

			if ($created) {
				$delta = array(
					'AuditDelta' => array(
						'property_name' => $property,
						'old_value' => '',
						'new_value' => $value,
					),
				);
			} else {
				if (Hash::check($this->_getOriginalDataForModel($Model), $property)
					&& Hash::get($this->_getOriginalDataForModel($Model), $property) != $value
				) {
					// If the property exists in the original _and_ the
					// value is different, store it.
					$delta = array(
						'AuditDelta' => array(
							'property_name' => $property,
							'old_value' => Hash::get($this->_getOriginalDataForModel($Model), $property),
							'new_value' => $value,
						),
					);
				}
			}
			if (!empty($delta)) {
				array_push($updates, $delta);
			}
		}

		// Insert an audit record if a new model record is being created
		// or if something we care about actually changed.
		if ($created || count($updates)) {
			$Model->Audit->create();
			$Model->Audit->save($data);

			if ($created) {
				if ($Model->hasMethod('afterAuditCreate')) {
					$Model->afterAuditCreate($Model);
				}
			} else {
				if ($Model->hasMethod('afterAuditUpdate')) {
					$Model->afterAuditUpdate($Model, $this->_original, $updates, $Model->Audit->id);
				}
			}
		}

		// Insert a delta record if something changed.
		if (count($updates)) {
			foreach ($updates as $delta) {
				$delta['AuditDelta']['audit_id'] = $Model->Audit->id;

				$Model->Audit->AuditDelta->create();
				$Model->Audit->AuditDelta->save($delta);

				if (!$created && $Model->hasMethod('afterAuditProperty')) {
					$Model->afterAuditProperty(
						$Model,
						$delta['AuditDelta']['property_name'],
						Hash::get($this->_getOriginalDataForModel($Model), $delta['AuditDelta']['property_name']),
						$delta['AuditDelta']['new_value']
					);
				}
			}
		}

		// Destroy the runtime association with the Audit.
		$Model->unbindModel(
			array('hasMany' => array('Audit'))
		);

		$this->_unsetOriginalDataForModel($Model);

		return true;
	}

/**
 * Executed after a model is deleted.
 *
 * @param Model $Model The model that is used for the delete operation.
 * @return void
 */
	public function afterDelete(Model $Model) {
		// Do not act on the AuditLog related models.
		if ($this->_isAuditLogModel($Model)) {
			return;
		}

		// If a currentUser() method exists in the model class (or, of
		// course, in a superclass) the call that method to pull all user
		// data. Assume than an ID field exists.
		$source = array();
		if ($Model->hasMethod('currentUser')) {
			$source = $Model->currentUser();
		} elseif ($Model->hasMethod('current_user')) {
			$source = $Model->current_user();
		}

		$audit = array($Model->alias => $this->_getOriginalDataForModel($Model));
		$data = array(
			'Audit' => array(
				'event' => 'DELETE',
				'model' => $Model->plugin ? $Model->plugin . '.' . $Model->alias : $Model->alias,
				'entity_id' => $Model->id,
				'request_id' => self::_requestId(),
				'json_object' => json_encode($audit),
				'source_id' => isset($source['id']) ? $source['id'] : null,
				'description' => isset($source['description']) ? $source['description'] : null,
			),
		);

		$this->Audit = ClassRegistry::init('AuditLog.Audit');
		$this->Audit->create();
		$this->Audit->save($data);

		$this->_unsetOriginalDataForModel($Model);
	}

/**
 * Get model data
 *
 * Retrieves the entire set model data contained to the primary
 * object and any/all HABTM associated data that has been configured
 * with the behavior.
 *
 * Additionally, for the HABTM data, all we care about is the IDs,
 * so the data will be reduced to an indexed array of those IDs.
 *
 * @param Model $Model The model that uses the behavior.
 * @return  array|false The model data or false.
 */
	protected function _getModelData(Model $Model) {
		// Turn cacheQueries off for model provided.
		$Model->cacheQueries = false;

		$this->_unsetVirtualFieldsTemporarily($Model);

		// Retrieve the model data along with its appropriate HABTM model data.
		$data = $Model->find(
			'first',
			array(
				'contain' => !empty($this->settings[$Model->alias]['habtm'])
					? array_values($this->settings[$Model->alias]['habtm'])
					: array(),
				'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
			)
		);

		// If we are using a SoftDelete behavior, $data will return empty after a delete.
		if (empty($data)) {
			return false;
		}

		$auditData = array(
			$Model->alias => isset($data[$Model->alias]) ? $data[$Model->alias] : array(),
		);

		foreach ($this->settings[$Model->alias]['habtm'] as $habtmModel) {
			if (array_key_exists($habtmModel, $Model->hasAndBelongsToMany) && isset($data[$habtmModel])) {
				$habtmIds = Hash::combine(
					$data[$habtmModel],
					'{n}.id',
					'{n}.id'
				);

				// Grab just the ID values and sort those.
				$habtmIds = array_values($habtmIds);
				sort($habtmIds);

				$auditData[$Model->alias][$habtmModel] = implode(',', $habtmIds);
			}
		}

		$this->_restoreVirtualFields($Model);

		return $auditData[$Model->alias];
	}

/**
 * Get request ID
 *
 * @return null|string The request ID.
 */
	protected function _requestId() {
		if (empty(self::$_requestId)) {
			// Class 'String' was deprecated in CakePHP 2.7 and replaced by 'CakeText' (Issue #41)
			$UuidClass = class_exists('CakeText') ? 'CakeText' : 'String';
			self::$_requestId = $UuidClass::uuid();
		}

		return self::$_requestId;
	}

/**
 * Set the original data for a given model
 *
 * @param Model $Model The model to set the original data for.
 * @param array $originalData The original data.
 * @return void
 */
	protected function _setOriginalDataForModel(Model $Model, $originalData) {
		$this->_original[$Model->alias] = $originalData;
	}

/**
 * Get the original data for a given model
 *
 * @param Model $Model The model to get the original data for.
 * @return array|null The original data for the given model.
 */
	protected function _getOriginalDataForModel(Model $Model) {
		return Hash::get($this->_original, $Model->alias, array());
	}

/**
 * Unset the original data for a given model
 *
 * @param Model $Model The model to unset the original data for.
 * @return void
 */
	protected function _unsetOriginalDataForModel(Model $Model) {
		unset($this->_original[$Model->alias]);
	}

/**
 * Check if the given model is one of AuditLog ones
 *
 * @param Model $Model The model to check
 * @return bool True if yes, else false.
 */
	protected function _isAuditLogModel(Model $Model) {
		return $Model->name === 'Audit' || $Model->name === 'AuditDelta';
	}

/**
 * Preserve and unset virtual fields and order as we don't need them and since they can cause SQL errors
 *
 * @param Model $Model The model to unset the virtual fields from.
 * @return void
 */
	protected function _unsetVirtualFieldsTemporarily(Model $Model) {
		$this->_modelVirtualFields = $Model->virtualFields;
		$this->_modelOrder = $Model->order;

		$Model->virtualFields = array();
		$Model->order = array();
	}

/**
 * Restore the virtual fields & order
 *
 * @param Model $Model The model to restore the virtual fields for.
 * @return void
 */
	protected function _restoreVirtualFields(Model $Model) {
		$Model->virtualFields = $this->_modelVirtualFields;
		$Model->order = $this->_modelOrder;
	}
}
