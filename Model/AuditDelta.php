<?php

/**
 * AuditDelta model
 */
class AuditDelta extends AuditLogAppModel {

	/**
	 * Belongs to relationships
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'AuditLog.Audit',
	);
}