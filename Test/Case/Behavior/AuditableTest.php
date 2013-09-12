<?php

App::uses( 'Model', 'Model' );
App::uses( 'AppModel', 'Model' );

/**
 * Article class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Article extends CakeTestModel {
  public $name = 'Article';
  public $actsAs = array(
    'AuditLog.Auditable' => array(
      'ignore' => array( 'ignored_field' ),
    )
  );
  public $belongsTo = array( 'Author' );
}

/**
 * Author class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Author extends CakeTestModel {
  public $name = 'Author';
  public $actsAs = array(
    'AuditLog.Auditable'
  );
  public $hasMany = array( 'Article' );
}

class Audit extends CakeTestModel {
  public $hasMany = array(
    'AuditDelta'
  );
}

class AuditDelta extends CakeTestModel {
  public $belongsTo = array(
    'Audit'
  );
}

/**
 * AuditableBehavior test class.
 */
class AuditableBehaviorTest extends CakeTestCase {
  /**
   * Fixtures associated with this test case
   *
   * @var array
   * @access public
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
   * @access public
   */
  public function setUp() {
    $this->Article = ClassRegistry::init( 'Article' );
  }
  
  /**
   * Method executed after each test
   *
   * @access public
   */
  public function tearDown() {
    unset( $this->Article );

    ClassRegistry::flush();
  }
  
  /**
   * Test the action of creating a new record.
   *
   * @todo  Test HABTM save
   */
  public function testCreate() {
    $new_article = array(
      'Article' => array(
        'user_id'   => 1,
        'author_id' => 1,
        'title'     => 'First Test Article', 
        'body'      => 'First Test Article Body', 
        'published' => 'N', 
      ),
    );
    
    $this->Article->save( $new_article );
    $audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $article = json_decode( $audit['Audit']['json_object'], true );
    
    $deltas = ClassRegistry::init( 'AuditDelta' )->find(
      'all',
      array(
        'recursive' => -1,
        'conditions' => array( 'AuditDelta.audit_id' => $audit['Audit']['id'] ),
      )
    );
    
    # Verify the audit record
    $this->assertEqual( 1, $article['Article']['user_id'] );
    $this->assertEqual( 'First Test Article', $article['Article']['title'] );
    $this->assertEqual( 'N', $article['Article']['published'] );
    
    #Verify that no delta record was created.
    $this->assertTrue( empty( $deltas ) );
  }

  /** 
   * Test saving multiple records with Model::saveAll()
   */
  public function testSaveAll() {
    # TEST A MODEL AND A SINGLE ASSOCIATED MODEL
    $data = array(
      'Article' => array(
        'user_id'   => 1,
        'title'     => 'Rob\'s Test Article', 
        'body'      => 'Rob\'s Test Article Body', 
        'published' => 'Y', 
      ),
      'Author' => array(
        'first_name' => 'Rob',
        'last_name' => 'Wilkerson',
      ),
    );

    $this->Article->saveAll( $data );
    $article_audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $article = json_decode( $article_audit['Audit']['json_object'], true );

    # Verify the audit record
    $this->assertEqual( 1, $article['Article']['user_id'] );
    $this->assertEqual( 'Rob\'s Test Article', $article['Article']['title'] );
    $this->assertEqual( 'Y', $article['Article']['published'] );
    
    # Verify that no delta record was created.
    $this->assertTrue( !isset( $article_audit['AuditDelta'] ) );

    $author_audit = ClassRegistry::init( 'Audit' )->find(
      'first',
      array(
        'recursive'  => -1,
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Author',
          'Audit.entity_id' => $this->Article->Author->getLastInsertId()
        )
      )
    );
    $author = json_decode( $author_audit['Audit']['json_object'], true );

    # Verify the audit record
    $this->assertEqual( $article['Article']['author_id'], $author['Author']['id'] );
    $this->assertEqual( 'Rob', $author['Author']['first_name'] );
    
    # Verify that no delta record was created.
    $this->assertTrue( !isset( $author_audit['AuditDelta'] ) );

    # TEST MULTIPLE RECORDS OF ONE MODEL

    $data = array(
        array(
          'Article' => array(
            'user_id'   => 1,
            'author_id' => 1,
            'title'     => 'Multiple Save 1 Title', 
            'body'      => 'Multiple Save 1 Body', 
            'published' => 'Y', 
          ),
        ),
        array(
          'Article' => array(
            'user_id'       => 2,
            'author_id'     => 2,
            'title'         => 'Multiple Save 2 Title', 
            'body'          => 'Multiple Save 2 Body', 
            'published'     => 'N', 
            'ignored_field' => 1,
          )
        ),        
        array(
          'Article' => array(
            'user_id'   => 3,
            'author_id' => 3,
            'title'     => 'Multiple Save 3 Title', 
            'body'      => 'Multiple Save 3 Body', 
            'published' => 'Y', 
          )
        ),
    );
    $this->Article->create();
    $this->Article->saveAll( $data ); 

    # Retrieve the audits for the last 3 articles saved
    $audits = ClassRegistry::init( 'Audit' )->find(
      'all',
      array(
        'conditions'        => array(
          'Audit.event'     => 'CREATE',
          'Audit.model'     => 'Article',
        ),
        'order' => array( 'Audit.entity_id DESC' ),
        'limit' => 3
      )
    );

    $article_1 = json_decode( $audits[2]['Audit']['json_object'], true );
    $article_2 = json_decode( $audits[1]['Audit']['json_object'], true );
    $article_3 = json_decode( $audits[0]['Audit']['json_object'], true );

    # Verify the audit records
    $this->assertEqual( 1, $article_1['Article']['user_id'] );
    $this->assertEqual( 'Multiple Save 1 Title', $article_1['Article']['title'] );
    $this->assertEqual( 'Y', $article_1['Article']['published'] );
    
    $this->assertEqual( 2, $article_2['Article']['user_id'] );
    $this->assertEqual( 'Multiple Save 2 Title', $article_2['Article']['title'] );
    $this->assertEqual( 'N', $article_2['Article']['published'] );
    
    $this->assertEqual( 3, $article_3['Article']['user_id'] );
    $this->assertEqual( 'Multiple Save 3 Title', $article_3['Article']['title'] );
    $this->assertEqual( 'Y', $article_3['Article']['published'] );
    
    # Verify that no delta records were created.
    $this->assertTrue( empty( $audits[0]['AuditDelta'] ) );
    $this->assertTrue( empty( $audits[1]['AuditDelta'] ) );
    $this->assertTrue( empty( $audits[2]['AuditDelta'] ) );
  }
  
  /**
   * Test editing an existing record.
   *
   * @todo  Test change to ignored field
   * @todo  Test HABTM save
   */
  public function testEdit() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );
    
    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'First Test Article', 
        'body'          => 'First Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N', 
      ),
    );
    
    # TEST SAVE WITH SINGLE PROPERTY UPDATE
    
    $this->Article->save( $new_article );
    $this->Article->saveField( 'title', 'First Test Article (Edited)' );
    
    $audit_records = $this->Audit->find(
      'all',
      array(
        'recursive' => 0,
        'conditions' => array(
          'Audit.model' => 'Article',
          'Audit.entity_id' => $this->Article->getLastInsertId()
        )
      )
    );
    $delta_records = $this->AuditDelta->find(
      'all',
      array(
        'recursive' => -1,
        'conditions' => array( 'AuditDelta.audit_id' => Set::extract( '/Audit/id', $audit_records ) ),
      )
    );
    
    $create_audit = Set::extract( '/Audit[event=CREATE]', $audit_records );
    $update_audit = Set::extract( '/Audit[event=EDIT]', $audit_records );
    
    # There should be 1 CREATE and 1 EDIT record
    $this->assertEqual( 2, count( $audit_records ) );
    
    # There should be one audit record for each event.
    $this->assertEqual( 1, count( $create_audit ) );
    $this->assertEqual( 1, count( $update_audit ) );
    
    # Only one property was changed
    $this->assertEqual( 1, count( $delta_records ) );
    
    $delta = array_shift( $delta_records );
    $this->assertEqual( 'First Test Article', $delta['AuditDelta']['old_value'] );
    $this->assertEqual( 'First Test Article (Edited)', $delta['AuditDelta']['new_value'] );

    # TEST UPDATE OF MULTIPLE PROPERTIES
    # Pause to simulate a gap between edits
    # This also allows us to retrieve the last edit for the next set
    # of tests.
    $this->Article->create(); # Clear the article id so we get  a new record.
    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'Second Test Article', 
        'body'          => 'Second Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N', 
      ),
    );
    $this->Article->save( $new_article );

    $updated_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'Second Test Article (Newly Edited)', 
        'body'          => 'Second Test Article Body (Also Edited)',
        'ignored_field' => 0,
        'published'     => 'Y', 
      ),
    );
    $this->Article->save( $updated_article );
    
    $last_audit = $this->Audit->find(
      'first',
      array(
        'contain'    => array( 'AuditDelta' ),
        'conditions' => array(
          'Audit.event'     => 'EDIT',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->id
        ),
        'order' => 'Audit.created DESC',
      )
    );

    # There are 4 changes, but one to an ignored field
    $this->assertEqual( 3, count( $last_audit['AuditDelta'] ) );
    $result = Set::extract( '/AuditDelta[property_name=title]/old_value', $last_audit );
    $this->assertEqual( 'Second Test Article', array_shift( $result ) );

    $result = Set::extract( '/AuditDelta[property_name=title]/new_value', $last_audit );
    $this->assertEqual( 'Second Test Article (Newly Edited)', array_shift( $result ) );

    $result = Set::extract( '/AuditDelta[property_name=body]/old_value', $last_audit );
    $this->assertEqual( 'Second Test Article Body', array_shift( $result ) );

    $result = Set::extract( '/AuditDelta[property_name=body]/new_value', $last_audit );
    $this->assertEqual( 'Second Test Article Body (Also Edited)', array_shift( $result ) );

    $result = Set::extract( '/AuditDelta[property_name=published]/old_value', $last_audit );
    $this->assertEqual( 'N', array_shift( $result ) );

    $result = Set::extract( '/AuditDelta[property_name=published]/new_value', $last_audit );
    $this->assertEqual( 'Y', array_shift( $result ) );

    # No delta should be reported against the ignored field.
    $this->assertIdentical( array(), Set::extract( '/AuditDelta[property_name=ignored_field]', $last_audit ) );
  }
  
  public function testIgnoredField() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );
    
    $new_article = array(
      'Article' => array(
        'user_id'       => 1,
        'author_id'     => 1,
        'title'         => 'First Test Article', 
        'body'          => 'First Test Article Body',
        'ignored_field' => 1,
        'published'     => 'N', 
      ),
    );
    
    # TEST NO AUDIT RECORD IF ONLY CHANGE IS IGNORED FIELD
    
    $this->Article->save( $new_article );
    $this->Article->saveField( 'ignored_field', '5' );
    
    $last_audit = $this->Audit->find(
      'count',
      array(
        'contain'    => array( 'AuditDelta' ),
        'conditions' => array(
          'Audit.event'     => 'EDIT',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $this->Article->id
        ),
        'order' => 'Audit.created DESC',
      )
    );
    
    $this->assertEqual( 0, $last_audit );
  }

  public function testDelete() {
    $this->Audit      = ClassRegistry::init( 'Audit' );
    $this->AuditDelta = ClassRegistry::init( 'AuditDelta' );
    $article = $this->Article->find(
      'first',
      array(
        'contain' => false,
        'order'   => array( 'rand()' ),
      )
    );
    
    $id = $article['Article']['id'];
    
    $this->Article->delete( $id );
    
    $last_audit = $this->Audit->find(
      'all',
      array(
        //'contain'    => array( 'AuditDelta' ), <-- What does this solve?
        'conditions' => array(
          'Audit.event'     => 'DELETE',
          'Audit.model'     => 'Article',
          'Audit.entity_id' => $id,
        ),
        'order' => 'Audit.created DESC',
      )
    );
    
    $this->assertEqual( 1, count( $last_audit ) );
  }
}