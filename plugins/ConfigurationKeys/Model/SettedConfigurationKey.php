<?php

class SettedConfigurationKey extends AppModel {

    public $validate = array(
        'name' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                'last' => true,
            ),
            'isUnique' => array(
                'rule' => array('isUnique'),                
            ),
        ),
        'value' => array(
            'notempty' => array(
                'rule' => array('notempty'),                
            ),
        ),
    );
}

?>