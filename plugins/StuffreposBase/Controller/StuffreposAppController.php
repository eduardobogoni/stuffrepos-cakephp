<?php

App::uses('Controller', 'Controller');

class StuffreposAppController extends Controller {

    public $components = array(        
        'StuffreposBase.ScaffoldUtil',
        'StuffreposBase.PaginatorUtil',        
    );
    public $helpers = array(
        'StuffreposBase.ActionList',
        'Html',
        'Session',
        'Form',
        'StuffreposBase.Lists',
        'StuffreposBase.AccessControl',
        'StuffreposBase.Menu',
        'StuffreposBase.ExtendedForm',
    );

    public function beforeFilter() {        
        $this->AccessControl = $this->Components->load('StuffreposBase.AccessControl');
        $this->AccessControl->initialize($this);
        $this->Session = $this->Components->load('Session');
        $this->Session->initialize($this);
        parent::beforeFilter();        
    }
    
    public function getUserAccess($user, $url) {
        return true;
    }

    protected function _getParentComponents() {
        return parent::$components;
    }
    
    public function flash($message, $url, $pause = 1, $layout = 'flash') {
        $this->Session->setFlash($message);
        $this->redirect($url);
    }

}
