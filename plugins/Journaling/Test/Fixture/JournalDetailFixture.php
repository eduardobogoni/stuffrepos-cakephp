<?php

class JournalDetailFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
        'journal_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),        
        'property' => array('type' => 'string', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
        'old_value' => array('type' => 'binary', 'length' => 4294967295, 'null' => true, 'default' => NULL),
        'value' => array('type' => 'binary', 'length' => 4294967295, 'null' => true, 'default' => NULL),
        'indexes' => array(
            'PRIMARY' => array('column' => 'id', 'unique' => 1),
            'journal_details_journal_id' => array('column' => 'journal_id'),
            'journal_details_property' => array('column' => 'property'),
        ),
        'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB'),
    );

}