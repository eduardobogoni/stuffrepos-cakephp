<?php

class JournalizedObjectFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'string_field' => array('type' => 'string', 'length' => 255, 'null' => true),
        'int_field' => array('type' => 'string', 'length' => 255, 'null' => true),
        'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
        'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
    );    
}