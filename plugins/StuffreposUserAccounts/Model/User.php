<?php

class User extends AppModel{        

    public $validate = array(
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
            ),
        ),
        'email' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'last' => true,
            ),
            'email' => array(
                'rule' => 'email',
                'required' => true,
            ),
            'isUnique' => array(
                'rule' => 'isUnique',
                'required' => true,
            ),
        ),
    );

}

?>
