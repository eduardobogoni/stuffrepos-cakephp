<?php

App::uses('StuffreposAppController', 'StuffreposBase.Controller');

class UsersController extends StuffreposAppController {

    public $scaffold;
    
    public $components = array(
        'StuffreposBase.ScaffoldUtil' => array(
            'setFields' => array(
                'name',
                'email',
                'created',
            )
        )
    );

}

?>
