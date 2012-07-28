<?php

class TraverserAuthorFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
    );
    public $records = array(
        array('id' => 1, 'name' => 'First Author'),
        array('id' => 2, 'name' => 'Second Author'),
    );
}

class TraverserAuthor extends Model {

    public $hasMany = array(
        'Article' => array(
            'className' => 'TraverserArticle',
            'foreignKey' => 'author_id',
        )
    );

}