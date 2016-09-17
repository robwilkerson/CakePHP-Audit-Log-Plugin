<?php

/**
 * Audit model
 */
class Audit extends AuditLogAppModel {

	/**
	 * Has many relationships
	 *
	 * @var array
	 */
	public $hasMany = array(
		'AuditLog.AuditDelta',
	);
}
