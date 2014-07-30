<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ExtendedFieldSetHelper extends AppHelper {

    public $helpers = array(
        'Base.CakeLayers',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.ViewUtil',
    );

    public function fieldSet($data, $scaffoldVars) {
        if (!ExtendedFieldsAccessControl::sessionUserHasFieldSetAccess($data)) {
            return false;
        } else if ($lines = $this->_lines($data, $scaffoldVars)) {
            $b = '';
            if (!empty($data['legend'])) {
                $b .= "<h3>{$data['legend']}</h3>";
            }
            return $b . $this->FieldSetLayout->fieldSet($lines);
        } else {
            return false;
        }
    }

    private function _lines($data, $scaffoldVars) {
        $lines = array();
        foreach ($data['lines'] as $scaffoldLine) {
            $line = $this->_line($scaffoldLine, $scaffoldVars);
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    private function _line($scaffoldLine, $scaffoldVars) {
        $line = array();
        foreach ($scaffoldLine as $field) {
            $line[$this->_fieldLabel($field)] = $this->_fieldValue(
                    $field, $scaffoldVars);
        }
        return $line;
    }

    private function _fieldLabel($field) {
        return __d('extended_scaffold',Inflector::humanize($field['name']));
    }

    private function _fieldValue($field, $scaffoldVars) {
        return $this->ViewUtil->autoFormat(ModelTraverser::displayValue(
                                $this->_model($scaffoldVars),
                                $scaffoldVars['instance'], $field['name']
        ));
    }

    private function _model($scaffoldVars) {
        if (empty($scaffoldVars['modelClass'])) {
            return $this->CakeLayers->getControllerDefaultModelClass();
        } else {
            return ClassRegistry::init($scaffoldVars['modelClass']);
        }
    }

}
