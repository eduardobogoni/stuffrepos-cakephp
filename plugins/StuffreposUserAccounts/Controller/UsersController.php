<?php

class UsersController extends AppController {

    public $scaffold;
    
    public $components = array(
        'StuffreposBase.ScaffoldUtil' => array(
            'indexSetFields' => array(
                'name',
                'email',
                'active',
                'created',
            ),
            'viewUnsetFields' => array(
                'id',
                'password',
            ),
            'editSetFields,addSetFields' => array(
                'id',
                'name',
                'email',
                'active',
            )
        )
    );

}

?>
