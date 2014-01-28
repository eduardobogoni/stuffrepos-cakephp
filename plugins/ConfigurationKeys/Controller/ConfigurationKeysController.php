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

    public function beforeRender() {
        parent::beforeRender();
        if ($this->request->params['action'] == 'edit') {
            $this->_settedValueListOptions();
        }
    }

    private function _settedValueListOptions() {
        if (!empty($this->request->data['ConfigurationKey']['name'])) {
            $listOptions = ConfigurationKeys::getKeyOptions(
                            $this->request->data['ConfigurationKey']['name']
                            , 'listOptions'
            );
            if (is_array($listOptions)) {
                $this->set(array(
                    'settedValues' => ArrayUtil::keysAsValues($listOptions)
                ));
            }
        }
    }

}

?>