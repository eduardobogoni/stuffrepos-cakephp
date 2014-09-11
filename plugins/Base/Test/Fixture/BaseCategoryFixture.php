<?php

App::uses('Model', 'Model');

class BaseCategoryFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'title' => array('type' => 'string', 'length' => 255, 'null' => false),
    );
    public $records = array(
        array('id' => 1, 'title' => 'First Category'),
        array('id' => 2, 'title' => 'Second Category'),
    );

}

class BaseCategory extends Model {

    public $hasMany = array(
        'Book' => array(
            'className' => 'BaseBook',
            'foreignKey' => 'category_id',
        )
    );

}