<?php

class CustomDataModelModelTestFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'color' => array('type' => 'string', 'length' => 255, 'null' => false),
    );

}

