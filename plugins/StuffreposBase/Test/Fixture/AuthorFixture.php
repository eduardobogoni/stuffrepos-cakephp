<?php

class AuthorFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
    );
    public $records = array(
        array('id' => 1, 'name' => 'First Author'),
        array('id' => 2, 'name' => 'Second Author'),
    );

}

class Author extends Model {

    public $hasMany = array(
        'Article'
    );

}