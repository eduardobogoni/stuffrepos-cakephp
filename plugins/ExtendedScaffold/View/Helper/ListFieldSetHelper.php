<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ListFieldSetHelper extends AppHelper {

    public $helpers = array(
        'ExtendedScaffold.Lists',
        'Base.CakeLayers',
    );

    public function fieldSet($fieldSet, $scaffoldVars, $options) {
        $b = '';
        if (!empty($fieldSet['legend'])) {
            $b .= "<h3>{$fieldSet['legend']}</h3>";
        }
        $b .= $this->Lists->rowsTable(
                $this->_fields($fieldSet)
                , $this->_rows($scaffoldVars, $fieldSet['listAssociation'])
                , Hash::merge($this->settings, array(
                    'model' => $this->_model($scaffoldVars, $fieldSet['listAssociation'])
                    , 'showActions' => false
                    , 'controller' => $this->_controller($scaffoldVars, $fieldSet['listAssociation'])
                ))
        );

        return $b;
    }

    private function _fields($fieldSet) {
        $ret = array();
        foreach ($fieldSet['lines'] as $fields) {
            foreach ($fields as $field) {
                $ret[$field['name']] = $field['options'];
            }
        }
        return $ret;
    }

    private function _rows($scaffoldVars, $listAssociation) {
        return ModelTraverser::value(
                        $this->CakeLayers->getModel($scaffoldVars['modelClass'])
                        , $scaffoldVars['instance']
                        , $listAssociation
        );
    }

    private function _model($scaffoldVars, $listAssociation) {
        return $this->CakeLayers->modelAssociationModel(
                        $scaffoldVars['modelClass']
                        , $listAssociation
                        , true
        );
    }

    private function _controller($scaffoldVars, $listAssociation) {
        $controller = $this->CakeLayers->getController(
                Inflector::pluralize($this->_model($scaffoldVars, $listAssociation)->name), false
        );
        if ($controller) {
            return $controller->name;
        } else {
            return null;
        }
    }

}
