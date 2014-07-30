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

App::uses('Component', 'Controller');
App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::uses('Basics', 'Base.Lib');

/**
 * 
 */
class ScaffoldUtilComponent extends Component {

    const OPTION_NAME_SEPARATOR = ',';
    public $components = array('Session');
    public $currentAction = null;
    public $defaultOptions;
    private $referer;
        
    public function startup(Controller $controller) {
        parent::startup($controller);
        $this->controller = $controller;
        if ($this->controller->modelClass) {
            $this->defaultOptions = array(
                'indexUnsetFields,viewUnsetFields' => array(
                    $this->controller->{$this->controller->modelClass}->primaryKey
                )
            );
        } else {
            $this->defaultOptions = array();
        }
        $this->_removeDeniedData($controller);
        $this->_fetchRefererFromRequest($controller);
    }

    public function beforeRender(Controller $controller) {
        parent::beforeRender($controller);
        $this->currentAction = $controller->params['action'];
        $this->_interceptScaffoldFields($controller);
        $this->_setRefererOnData($controller);
    }

    public function referer() {
        return $this->referer;
    }

    private function _interceptScaffoldFields(\Controller $controller) {
        if (isset($controller->viewVars['scaffoldFields'])) {
            $controller->set('scaffoldFields', $this->_buildScaffoldFields($controller));
        }
    }

    private function _buildScaffoldFields(\Controller $controller) {
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

        if (empty($scaffoldFields['_extended'])) {
            if ($this->_getActionOption('unsetFields')) {
                $tempFields = array();
                foreach ($scaffoldFields as $key => $value) {
                    $field = is_array($value) ? $key : $value;
                    if (!in_array($field, $this->_getActionOption('unsetFields'))) {
                        $tempFields[$key] = $value;
                    }
                }
                $scaffoldFields = $tempFields;
            }
        }
        return $scaffoldFields;
    }

    private function _removeDeniedData(\Controller $controller) {
        foreach ($controller->request->data as $modelAlias => $fields) {
            foreach (array_keys($fields) as $field) {

                if ($this->_shouldRemoveData($controller, $modelAlias, $field)) {
                    unset($controller->request->data[$modelAlias][$field]);
                }
            }
        }
    }

    private function _shouldRemoveData(\Controller $controller, $modelAlias, $field) {
        $scaffolfFields = $this->_buildScaffoldFields($controller);
        $definition = ExtendedFieldsParser::parseFieldsets($scaffolfFields);
        foreach ($definition as $fieldSet) {
            foreach ($fieldSet['lines'] as $line) {
                foreach ($line as $extendedFieldsParserField) {
                    if ($this->_fieldNameEquals($controller, $extendedFieldsParserField, "$modelAlias.$field")) {
                        return !$this->_hasFieldAccess($extendedFieldsParserField)
                                || !$this->_hasFieldSetAccess($fieldSet);
                    }
                }
            }
        }
        return true;
    }

    private function _fieldNameEquals(\Controller $controller, $extendedFieldsParserField, $name) {
        $defaultModel = empty($controller->uses[0]) ?
                null :
                $controller->{$controller->uses[0]}->alias;
        return Basics::fieldFullName($extendedFieldsParserField['name'], $defaultModel) ==
        Basics::fieldFullName($name, $defaultModel);
    }

    private function _hasFieldAccess($extendedFieldsParserField) {
        return $this->_hasFieldSetAccess($extendedFieldsParserField['options']);
    }
    
    private function _hasFieldSetAccess($extendedFieldsParserFieldSet) {
        if (!empty($extendedFieldsParserFieldSet['accessObject'])) {
            if (empty($extendedFieldsParserFieldSet['accessObjectType'])) {
                return AccessControlComponent::sessionUserHasAccess($extendedFieldsParserFieldSet['accessObject']);
            }
            else {
                return AccessControlComponent::sessionUserHasAccess($extendedFieldsParserFieldSet['accessObject'], $extendedFieldsParserFieldSet['accessObjectType']);
            }           
        }
        else {
            return true;
        }
    }

    private function _fetchRefererFromRequest(\Controller $controller) {
        if (empty($controller->request->data['_ScaffoldUtil']['referer'])) {
            $this->referer = $controller->referer();
        }
        else {
            $this->referer = $controller->request->data['_ScaffoldUtil']['referer'];
        }        
    }
    
    private function _setRefererOnData(\Controller $controller) {
        $controller->request->data['_ScaffoldUtil']['referer'] = $this->referer;
    }

    public function addJavascriptLink($file) {
        $this->controller->params[__CLASS__]['javascriptLinks'][] = $file;
    }
    
    public function render($action, $scaffoldFields = array()) {                
        App::import('Lib', 'Scaffold');
        
        $this->controller->viewClass = 'Scaffold';
        $this->controller->set('pluralVar',Inflector::variable($this->controller->name));
        $this->controller->set('singularVar', Inflector::variable($this->controller->modelClass));
        $this->controller->set('singularHumanName', Inflector::humanize(Inflector::underscore($this->controller->modelClass)));
        $this->controller->set('scaffoldFields', $scaffoldFields);
        $this->controller->render($action);
    }

    private function _getActionOption($name) {
        $action = ($this->currentAction ? $this->currentAction : $this->controller->params['action']);
        $optionName = Inflector::camelize($action . '_' . Inflector::underscore($name));
        $optionName = strtolower(substr($optionName, 0, 1)) . substr($optionName, 1, strlen($optionName) - 1);
        return $this->_findOption($optionName);                
    }
    
    private function _findOption($optionName) {
        if (is_array($this->settings)) {
            foreach ($this->settings as $key => $value) {
                foreach (explode(self::OPTION_NAME_SEPARATOR, $key) as $keyOption) {
                    if (trim($keyOption) == trim($optionName)) {
                        return $value;
                    }
                }
            }
        }

        foreach($this->defaultOptions as $key => $value) {
            foreach(explode(self::OPTION_NAME_SEPARATOR,$key) as $keyOption) {
                if (trim($keyOption) == trim($optionName)) {
                    return $value;
                }
            }
        }
        
        return false;
    }

}
