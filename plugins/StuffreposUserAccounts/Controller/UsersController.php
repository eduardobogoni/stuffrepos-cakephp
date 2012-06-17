<?php

class UsersController extends AppController {

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
