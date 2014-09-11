<?php

App::uses('Model', 'Model');

class BaseBookFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'title' => array('type' => 'string', 'length' => 255, 'null' => false),
        'author_id' => array('type' => 'integer', 'null' => true),
        'category_id' => array('type' => 'integer', 'null' => false),
    );
    public $records = array(
        array('id' => 1, 'title' => 'First Book', 'author_id' => 1, 'category_id' => 1),
        array('id' => 2, 'title' => 'Second Book', 'author_id' => 2, 'category_id' => 1),
        array('id' => 3, 'title' => 'Third Book', 'author_id' => 2, 'category_id' => 2),
        array('id' => 4, 'title' => 'Third Book', 'author_id' => null, 'category_id' => 2),
    );

}

class BaseBook extends Model {

    public $belongsTo = array(
        'Author' => array(
            'className' => 'BaseAuthor',
            'foreignKey' => 'author_id',
        ),
        'Category' => array(
            'className' => 'BaseCategory',
            'foreignKey' => 'category_id',
        )
    );

}
