<?php

App::uses('AppModel', 'Model');

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

    public $hasAndBelongsToMany = array(
        'Tag' => array(
            'className' => 'Tag',
            'joinTable' => 'articles_tags',
            'foreignKey' => 'article_id',
            'associationForeignKey' => 'tag_id'
        )
    );
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
 * Class TagB- Habtm Test Model
 */
class Tag extends CakeTestModel {

    /**
     * The name of the model
     *
     * @var string
     */
    public $name = 'Tag';

    /**
     * Table of the model
     *
     * @var string
     */
    public $useTable = 'tags';

    /**
     * The behaviors
     *
     * @var array
     */
    public $actsAs = array(
        'AuditLog.Auditable',
    );

    /**
     * HasAndBelongsToMany relationships
     *
     * @var array
     */
    public $hasAndBelongsToMany = array(
        'Article' => array(
            'className' => 'Article',
            'joinTable' => 'articles_tags',
            'foreignKey' => 'tag_id',
            'associationForeignKey' => 'article_id'
        )
    );

}