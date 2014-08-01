<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ExtendedFieldSetHelper extends AppHelper {

    public $helpers = array(
        'Base.CakeLayers',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.ViewUtil',
    );

    public function fieldSet(\FieldSetDefinition $fieldSet, $scaffoldVars) {
        $b = '';
        if ($fieldSet->getLabel()) {
            $b .= "<h3>{$fieldSet->getLabel()}</h3>";
        }
        return $b . $this->FieldSetLayout->fieldSet($this->_lines($fieldSet, $scaffoldVars));
    }

    private function _lines(\FieldSetDefinition $fieldSet, $scaffoldVars) {
        $lines = array();
        foreach ($fieldSet->getLines() as $fieldsLine) {
            $fieldsLineResult = $this->_line($fieldsLine, $scaffoldVars);
            if (!empty($fieldsLineResult)) {
                $lines[] = $fieldsLineResult;
            }
        }
        return $lines;
    }

    private function _line(\FieldRowDefinition $line, $scaffoldVars) {
        $ret = array();
        foreach ($line->getFields() as $field) {
            $ret[$this->_fieldLabel($field)] = $this->_fieldValue(
                    $field, $scaffoldVars);
        }
        return $ret;
    }

    private function _fieldLabel($field) {
        return __d('extended_scaffold', Inflector::humanize($field->getName()));
    }

    private function _fieldValue(\FieldDefinition $field, $scaffoldVars) {
        return $this->ViewUtil->autoFormat(
                        ModelTraverser::displayValue(
                                $this->_model($scaffoldVars)
                                , $scaffoldVars['instance']
                                , $field->getName()
                        )
        );
    }

    private function _model($scaffoldVars) {
        if (empty($scaffoldVars['modelClass'])) {
            return $this->CakeLayers->getControllerDefaultModelClass();
        } else {
            return ClassRegistry::init($scaffoldVars['modelClass']);
        }
    }

}
