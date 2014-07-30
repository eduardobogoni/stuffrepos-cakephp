<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ExtendedFieldSetHelper extends AppHelper {

    public $helpers = array(
        'Base.CakeLayers',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.ViewUtil',
    );

    public function fieldSet($data, $scaffoldVars) {
        $b = '';
        if (!empty($data['legend'])) {
            $b .= "<h3>{$data['legend']}</h3>";
        }
        $b .= $this->FieldSetLayout->fieldSet(
                $this->_lines($data, $scaffoldVars)
        );
        return $b;
    }

    private function _lines($data, $scaffoldVars) {
        $lines = array();
        foreach ($data['lines'] as $scaffoldLine) {
            $line = array();
            foreach ($scaffoldLine as $field) {
                $line[$this->_fieldLabel($field)] = $this->_fieldValue(
                        $field, $scaffoldVars);
            }
            $lines[] = $line;
        }
        return $lines;
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
