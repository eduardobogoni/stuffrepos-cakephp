<?php

App::uses('AppController', 'Controller');

/**
 * ImapParserConfigurations Controller
 *
 */
class ImapParserConfigurationsController extends AppController {

    /**
     * Scaffold
     *
     * @var mixed
     */
    public $scaffold;

    
    public function beforeRender() {
        parent::beforeRender();
        $this->set($this->ImapParserConfiguration->listsFromValidationsInList());
    }
}
