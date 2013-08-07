<?php

class TransactionModelModelTestFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
        'tableParameters' => array(
            'engine' => 'InnoDB',
        )
    );

}

