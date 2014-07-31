<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');
App::uses('ExtendedField', 'ExtendedScaffold.Lib');

class ListFieldSetHelper extends AppHelper {

    public $helpers = array(
        'ExtendedScaffold.Lists',
        'Base.CakeLayers',
    );

    public function fieldSet(\ExtendedFieldSet $fieldSet, $scaffoldVars, $options) {
        if (!$fieldSet->getListAssociation()) {
            throw new Exception('$fieldSet->getListAssociation() is empty: '.print_r($fieldSet->getOptions(), true));
        }
        $b = '';
        if ($fieldSet->getLabel()) {
            $b .= "<h3>{$fieldSet->getLabel()}</h3>";
        }
        $b .= $this->Lists->rowsTable(
                $this->_fields($fieldSet)
                , $this->_rows($scaffoldVars, $fieldSet->getListAssociation())
                , Hash::merge($this->settings, array(
                    'model' => $this->_model($scaffoldVars, $fieldSet->getListAssociation())
                    , 'showActions' => false
                    , 'controller' => $this->_controller($scaffoldVars, $fieldSet->getListAssociation())
                ))
        );

        return $b;
    }

    private function _fields(\ExtendedFieldSet $fieldSet) {
        $ret = array();
        foreach ($fieldSet->getLines() as $line) {
            foreach($line->getFields() as $field) {
                $ret[$field->getName()] = $field->getOptions();
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
