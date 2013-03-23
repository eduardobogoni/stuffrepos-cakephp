<?php

class ConfigurationKeysController extends AppController {

    public $scaffold;
    public $paginate = array(
        'limit' => 10000
    );
    public $notModuleActions = array(
        'add'
    );
    public $components = array(
        'ExtendedScaffold.ScaffoldUtil' => array(
            'editSetFields' => array(
                'name' => array(
                    'type' => 'string',
                    'readonly' => true
                ),
                'description' => array(
                    'type' => 'string',
                    'readonly' => true
                ),
                'default_value' => array(
                    'type' => 'string',
                    'readonly' => true
                ),
                'setted_value',
            ),
            'indexUnsetFields' => array(
                'default_value',
                'setted_value'
            )
        )
    );

    public function add() {
        $this->redirect(array('action' => 'index'));
    }

}

?>