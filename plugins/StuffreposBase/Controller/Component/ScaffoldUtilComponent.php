<?php

/*
 * Copyright 2010 Eduardo H. Bogoni <eduardobogoni@gmail.com>
 *
 * This file is part of CakePHP Bog Util.
 *
 * CakePHP Bog Util is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * CakePHP Bog Util is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * CakePHP Bog Util. If not, see http://www.gnu.org/licenses/.
 */

/**
 * 
 */
class ScaffoldUtilComponent extends Component {

    const OPTION_NAME_SEPARATOR = ',';
    public $components = array('Session');
    public $currentAction = null;
        
    public function initialize(Controller $controller) {
        $this->controller = $controller;
    }

    public function beforeRender(Controller $controller) {
        parent::beforeRender($controller);
        if (isset($controller->viewVars['scaffoldFields'])) {

            if ($this->_getActionOption('setFields')) {
                $scaffoldFields = $this->_getActionOption('setFields');
            } else {
                $scaffoldFields = $controller->viewVars['scaffoldFields'];
            }

            if ($this->_getActionOption('appendFields')) {
                $scaffoldFields = array_merge(
                        $scaffoldFields, $this->_getActionOption('appendFields')
                );
            }

            if ($this->_getActionOption('unsetFields')) {
                $tempFields = array();
                foreach ($scaffoldFields as $field) {
                    if (!in_array($field, $this->_getActionOption('unsetFields'))) {
                        $tempFields[] = $field;
                    }
                }
                $scaffoldFields = $tempFields;
            }

            $controller->set('scaffoldFields', $scaffoldFields);            
        }
    }

    public function addJavascriptLink($file) {
        $this->controller->params[__CLASS__]['javascriptLinks'][] = $file;
    }
    
    public function render($action, $scaffoldFields = array()) {                
        App::import('Lib', 'Scaffold');
        
        $this->controller->view = 'Scaffold';
        $this->controller->set('pluralVar',Inflector::variable($this->controller->name));
        $this->controller->set('singularHumanName', Inflector::humanize(Inflector::underscore($this->controller->modelClass)));
        $this->controller->set('scaffoldFields', $scaffoldFields);
        $this->controller->render('edit');
    }

    private function _getActionOption($name) {
        return empty($this->settings[$name]) ? false : $this->settings[$name];
    }    

}
