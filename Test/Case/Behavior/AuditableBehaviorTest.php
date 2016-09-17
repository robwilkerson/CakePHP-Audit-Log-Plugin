<?php

App::uses('AppModel', 'Model');
App::uses('Audit', 'AuditLog.Model');
App::uses('AuditDelta', 'AuditLog.Model');

/**
 * Article test model
 */
class Article extends CakeTestModel {

/**
 * The name of the model
 *
 * @var string
 */
	public $name = 'Article';

/**
 * The behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'AuditLog.Auditable' => array(
			'ignore' => array('ignored_field'),
		),
	);

/**
 * Belongs to relationships
 *
 * @var array
 */
	public $belongsTo = array('Author');
}

/**
 * Author test model
 */
class Author extends CakeTestModel {

/**
 * The name of the model
 *
 * @var string
 */
	public $name = 'Author';

/**
 * The behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'AuditLog.Auditable',
	);

/**
 * Has many relationships
 *
 * @var array
 */
	public $hasMany = array('Article');
}

/**
 * AuditableBehavior Tests
 */
class AuditableBehaviorTest extends CakeTestCase {

/**
 * Fixtures associated with this test case
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.audit_log.article',
		'plugin.audit_log.author',
		'plugin.audit_log.audit',
		'plugin.audit_log.audit_delta',
	);

/**
 * Method executed before each test
 *
 * @return void
 */
	public function setUp() {
		$this->Article = ClassRegistry::init('Article');
	}

/**
 * Test the action of creating a new record.
 *
 * @return void
 * @todo Test HABTM save.
 */
	public function testCreate() {
		$newArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'First Test Article',
				'body' => 'First Test Article Body',
				'published' => 'N',
			),
		);

		$this->Article->save($newArticle);
		$audit = ClassRegistry::init('AuditLog.Audit')->find(
			'first',
			array(
				'recursive' => -1,
				'conditions' => array(
					'Audit.event' => 'CREATE',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->getLastInsertId(),
				),
			)
		);
		$article = json_decode($audit['Audit']['json_object'], true);

		$deltas = ClassRegistry::init('AuditLog.AuditDelta')->find(
			'all',
			array(
				'recursive' => -1,
				'conditions' => array('AuditDelta.audit_id' => $audit['Audit']['id']),
			)
		);

		// Verify the audit record.
		$this->assertEquals(1, $article['Article']['user_id']);
		$this->assertEquals('First Test Article', $article['Article']['title']);
		$this->assertEquals('N', $article['Article']['published']);

		// Verify that delta record were created, too.
		$this->assertCount(6, $deltas);
	}

/**
 * Test the action of creating a new record when some values are empty.
 *
 * @return void
 * @todo Test HABTM save.
 */
	public function testCreateWithEmptyValues() {
		$newArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'First Test Article',
				'body' => ''
                // 'published' should be set with default value 'N' (see Fixture)
			),
		);

		$this->Article->save($newArticle);
		$audit = ClassRegistry::init('AuditLog.Audit')->find(
			'first',
			array(
				'recursive' => -1,
				'conditions' => array(
					'Audit.event' => 'CREATE',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->getLastInsertId(),
				),
			)
		);
		$article = json_decode($audit['Audit']['json_object'], true);

		$deltas = ClassRegistry::init('AuditLog.AuditDelta')->find(
			'all',
			array(
				'recursive' => -1,
				'conditions' => array('AuditDelta.audit_id' => $audit['Audit']['id']),
			)
		);

		// Verify the audit record.
		$this->assertEquals(1, $article['Article']['user_id']);
		$this->assertEquals('First Test Article', $article['Article']['title']);
		$this->assertEquals('N', $article['Article']['published']);

		// Verify that delta record were created, too.
		$this->assertCount(6, $deltas);
	}

/**
 * Test saving multiple records with Model::saveAll()
 *
 * @return void
 */
	public function testSaveAll() {
		// Test a model and a single associated model.
		$data = array(
			'Article' => array(
				'user_id' => 1,
				'title' => 'Rob\'s Test Article',
				'body' => 'Rob\'s Test Article Body',
				'published' => 'Y',
			),
			'Author' => array(
				'first_name' => 'Rob',
				'last_name' => 'Wilkerson',
			),
		);

		$this->Article->saveAll($data);

		$auditModel = ClassRegistry::init('AuditLog.Audit');

		$articleAudit = $auditModel->find(
			'first',
			array(
				'recursive' => 1,
				'conditions' => array(
					'Audit.event' => 'CREATE',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->getLastInsertId(),
				),
			)
		);
		$article = json_decode($articleAudit['Audit']['json_object'], true);

		// Verify the audit record.
		$this->assertEquals(1, $article['Article']['user_id']);
		$this->assertEquals('Rob\'s Test Article', $article['Article']['title']);
		$this->assertEquals('Y', $article['Article']['published']);

		// Verify the delta records were created.
		$this->assertCount(6, $articleAudit['AuditDelta']);

		$authorAudit = $auditModel->find(
			'first',
			array(
				'recursive' => 1,
				'conditions' => array(
					'Audit.event' => 'CREATE',
					'Audit.model' => 'Author',
					'Audit.entity_id' => $this->Article->Author->getLastInsertId(),
				),
			)
		);
		$author = json_decode($authorAudit['Audit']['json_object'], true);

		// Verify the audit record.
		$this->assertEquals($article['Article']['author_id'], $author['Author']['id']);
		$this->assertEquals('Rob', $author['Author']['first_name']);

		// Verify the delta records were created.
		$this->assertCount(3, $authorAudit['AuditDelta']);

		// Test multiple records of one model.
		$data = array(
			array(
				'Article' => array(
					'user_id' => 1,
					'author_id' => 1,
					'title' => 'Multiple Save 1 Title',
					'body' => 'Multiple Save 1 Body',
					'published' => 'Y',
				),
			),
			array(
				'Article' => array(
					'user_id' => 2,
					'author_id' => 2,
					'title' => 'Multiple Save 2 Title',
					'body' => 'Multiple Save 2 Body',
					'published' => 'N',
					'ignored_field' => 1,
				),
			),
			array(
				'Article' => array(
					'user_id' => 3,
					'author_id' => 3,
					'title' => 'Multiple Save 3 Title',
					'body' => 'Multiple Save 3 Body',
					'published' => 'Y',
				),
			),
		);

		$this->Article->create();
		$this->Article->saveAll($data);

		// Retrieve the audits for the last 3 articles saved.
		$audits = $auditModel->find(
			'all',
			array(
				'conditions' => array(
					'Audit.event' => 'CREATE',
					'Audit.model' => 'Article',
				),
				'order' => array('Audit.entity_id DESC'),
				'limit' => 3,
			)
		);

		$article1 = json_decode($audits[2]['Audit']['json_object'], true);
		$article2 = json_decode($audits[1]['Audit']['json_object'], true);
		$article3 = json_decode($audits[0]['Audit']['json_object'], true);

		// Verify the audit records.
		$this->assertEquals(1, $article1['Article']['user_id']);
		$this->assertEquals('Multiple Save 1 Title', $article1['Article']['title']);
		$this->assertEquals('Y', $article1['Article']['published']);

		$this->assertEquals(2, $article2['Article']['user_id']);
		$this->assertEquals('Multiple Save 2 Title', $article2['Article']['title']);
		$this->assertEquals('N', $article2['Article']['published']);

		$this->assertEquals(3, $article3['Article']['user_id']);
		$this->assertEquals('Multiple Save 3 Title', $article3['Article']['title']);
		$this->assertEquals('Y', $article3['Article']['published']);

		// Verify that no delta records were created.
		$this->assertCount(6, $audits[0]['AuditDelta']);
		$this->assertCount(6, $audits[1]['AuditDelta']);
		$this->assertCount(6, $audits[2]['AuditDelta']);
	}

/**
 * Test editing an existing record.
 *
 * @return void
 * @todo Test change to ignored field.
 * @todo Test HABTM save.
 */
	public function testEdit() {
		$this->Audit = ClassRegistry::init('AuditLog.Audit');
		$this->AuditDelta = ClassRegistry::init('AuditLog.AuditDelta');

		$newArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'First Test Article',
				'body' => 'First Test Article Body',
				'ignored_field' => 1,
				'published' => 'N',
			),
		);

		// Test save with single property update.
		$this->Article->save($newArticle);
		$this->Article->saveField('title', 'First Test Article (Edited)');

		$auditRecords = $this->Audit->find(
			'all',
			array(
				'recursive' => 0,
				'conditions' => array(
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->getLastInsertId(),
				),
			)
		);

		// There should be 1 CREATE and 1 EDIT record.
		$this->assertCount(2, $auditRecords);

		$createAudit = Hash::extract($auditRecords, '{n}.Audit[event=CREATE]');
		$updateAudit = Hash::extract($auditRecords, '{n}.Audit[event=EDIT]');

		// There should be one audit record for each event.
		$this->assertCount(1, $createAudit);
		$this->assertCount(1, $updateAudit);

		$deltaRecords = $this->AuditDelta->find(
			'all',
			array(
				'recursive' => -1,
				'conditions' => array('AuditDelta.audit_id' => Hash::extract($updateAudit, '{n}.id')),
			)
		);

		// Only one property was changed.
		$this->assertCount(1, $deltaRecords);

		$delta = array_shift($deltaRecords);
		$this->assertEquals('First Test Article', $delta['AuditDelta']['old_value']);
		$this->assertEquals('First Test Article (Edited)', $delta['AuditDelta']['new_value']);

		// Test Update Of multiple properties.
		// Pause to simulate a gap between edits.
		// This also allows us to retrieve the last edit for the next set of tests.
		$this->Article->create(); // Clear the article id so we get a new record.
		$newArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'Second Test Article',
				'body' => 'Second Test Article Body',
				'ignored_field' => 1,
				'published' => 'N',
			),
		);
		$this->Article->save($newArticle);

		$updatedArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'Second Test Article (Newly Edited)',
				'body' => 'Second Test Article Body (Also Edited)',
				'ignored_field' => 0,
				'published' => 'Y',
			),
		);
		$this->Article->save($updatedArticle);

		$lastAudit = $this->Audit->find(
			'first',
			array(
				'contain' => array('AuditDelta'),
				'conditions' => array(
					'Audit.event' => 'EDIT',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->id,
				),
				'order' => 'Audit.created DESC',
			)
		);

		// There are 4 changes, but one to an ignored field.
		$this->assertCount(3, $lastAudit['AuditDelta']);
		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=title].old_value');
		$this->assertEquals('Second Test Article', array_shift($result));

		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=title].new_value');
		$this->assertEquals('Second Test Article (Newly Edited)', array_shift($result));

		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=body].old_value');
		$this->assertEquals('Second Test Article Body', array_shift($result));

		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=body].new_value');
		$this->assertEquals('Second Test Article Body (Also Edited)', array_shift($result));

		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=published].old_value');
		$this->assertEquals('N', array_shift($result));

		$result = Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=published].new_value');
		$this->assertEquals('Y', array_shift($result));

		// No delta should be reported against the ignored field.
		$this->assertSame(array(), Hash::extract($lastAudit, 'AuditDelta.{n}[property_name=ignored_field]'));
	}

/**
 * Test ignoring fields
 *
 * @return void
 */
	public function testIgnoredField() {
		$this->Audit = ClassRegistry::init('AuditLog.Audit');
		$this->AuditDelta = ClassRegistry::init('AuditLog.AuditDelta');

		$newArticle = array(
			'Article' => array(
				'user_id' => 1,
				'author_id' => 1,
				'title' => 'First Test Article',
				'body' => 'First Test Article Body',
				'ignored_field' => 1,
				'published' => 'N',
			),
		);

		// Test no audit record, if only change is on ignored field.

		$this->Article->save($newArticle);
		$this->Article->saveField('ignored_field', '5');

		$lastAudit = $this->Audit->find(
			'count',
			array(
				'contain' => array('AuditDelta'),
				'conditions' => array(
					'Audit.event' => 'EDIT',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $this->Article->id,
				),
				'order' => 'Audit.created DESC',
			)
		);

		$this->assertEquals(0, $lastAudit);
	}

/**
 * Test delete action
 *
 * @return void
 */
	public function testDelete() {
		$this->Audit = ClassRegistry::init('AuditLog.Audit');
		$this->AuditDelta = ClassRegistry::init('AuditLog.AuditDelta');
		$article = $this->Article->find(
			'first',
			array(
				'contain' => false,
				'order' => array('rand()'),
			)
		);

		$id = $article['Article']['id'];

		$this->Article->delete($id);

		$lastAudit = $this->Audit->find(
			'all',
			array(
				'conditions' => array(
					'Audit.event' => 'DELETE',
					'Audit.model' => 'Article',
					'Audit.entity_id' => $id,
				),
				'order' => 'Audit.created DESC',
			)
		);

		$this->assertCount(1, $lastAudit);
	}
}
