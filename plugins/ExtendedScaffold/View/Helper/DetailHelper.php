<?php

App::uses('AppHelper', 'View/Helper');
App::uses('ExtendedFieldsAccessControl', 'ExtendedScaffold.Lib');

class DetailHelper extends AppHelper {

    public $helpers = array(        
        'AccessControl.AccessControl',
        'Base.CakeLayers',
        'ExtendedScaffold.Lists',
        'ExtendedScaffold.ViewUtil',
        'ExtendedScaffold.ExtendedFieldSet',
        'ExtendedScaffold.ListFieldSet',
    );

    /**
     *
     * @var array
     */
    public $settings = array(
        'listSettings' => array(),
    );

    /**
     *
     * @param array $fields 
     * @return string
     */
    public function commonViewFieldList($fields) {
        $b = "\n<table class='viewFieldList'>\n";
        foreach ($fields as $label => $value) {
            $b .= "\t" . (is_array($value) ? $this->viewField($value) : $this->viewField($label, $value)) . "\n";
        }
        $b .= "\n</table>\n";
        return $b;
    }

    /**
     * Cria uma lista com entradas "Rótulo:Valor" para valores vindos de um
     * Scaffold de Visão.
     * @param type $instance
     * @param type $fields
     * @param type $associations 
     */
    public function scaffoldViewFieldList($instance, $fields, $options = array()) {
        if (empty($options['modelClass'])) {
            $modelClass = $this->CakeLayers->getControllerDefaultModelClass();
        } else {
            $modelClass = $options['modelClass'];
        }

        if (empty($options['associations'])) {
            $associations = $this->CakeLayers->getModelAssociations($modelClass);
        } else {
            $associations = $options['associations'];
        }

        if (ExtendedFieldsParser::isExtendedFieldsDefinition($fields)) {
            return $this->_scaffoldExtendedFieldList(
                            $instance
                            , ExtendedFieldsAccessControl::parseFieldsets($fields)
                            , $associations
                            , $modelClass
            );
        } else {
            return $this->_scaffoldCommonFieldList($instance, $fields, $associations, $modelClass);
        }
    }

    /**
     *
     * @param mixed $label Uma string com o rótulo a ser usado ou um array com 
     * as seguintes opções: fieldName, value, label, type
     * @param mixed $value
     * @param mixed $valueType
     * @return string 
     */
    public function viewField($label, $value = null,
            $valueType = ViewUtilHelper::VALUE_TYPE_UNKNOWN) {
        $class = null;
        if ($this->viewFieldCount++ % 2 == 0) {
            $class = ' class="altrow"';
        }

        $field = $this->_extractFieldData($label, $value, $valueType);

        switch ($field['type']) {
            case ViewUtilHelper::VALUE_TYPE_BOOLEAN:
                $value = $this->ViewUtil->yesNo($field['value']);
                break;
            case ViewUtilHelper::VALUE_TYPE_UNKNOWN:
            default:
                $value = $this->ViewUtil->autoFormat($field['value']);
        }

        $buffer = "<tr{$class}>";
        $buffer .= "\t\t<th>" . __d('extended_scaffold', Inflector::humanize($field['label']), true) . "</th>\n";
        $buffer .= "\t\t<td>\n\t\t\t" . $value . "\n&nbsp;\t\t</td>\n";
        $buffer .= "</tr>";

        return $buffer;
    }

    private function _scaffoldCommonFieldList($instance, $fields, $associations,
            $modelClass) {
        $i = 0;
        $viewFields = array();
        foreach ($fields as $_field) {

            $isKey = false;

            if (!empty($associations['belongsTo'])) {
                foreach ($associations['belongsTo'] as $_alias => $_details) {
                    if ($_field === $_details['foreignKey']) {
                        $isKey = true;
                        $viewFields[] = array(
                            'label' => __d('extended_scaffold', Inflector::humanize($_alias), true),
                            'value' => $this->AccessControl->linkOrText(
                                    ModelTraverser::value($this->_getCurrentController()->{$modelClass}, $instance, "$_alias.{$_details['displayField']}")
                                    , array(
                                'controller' => $_details['controller']
                                , 'action' => 'view'
                                , ModelTraverser::value($this->_getCurrentController()->{$modelClass}, $instance, "$_alias.{$_details['primaryKey']}")
                                    )
                            )
                            , 'fieldName' => $_field
                        );

                        break;
                    }
                }
            }
            if ($isKey !== true) {
                $viewFields[] = array(
                    'label' => __d('extended_scaffold', Inflector::humanize($_field), true),
                    'value' => ModelTraverser::value($this->_getCurrentController()->{$modelClass}, $instance, $_field),
                    'fieldName' => $_field,
                );
            }
        }

        return $this->commonViewFieldList($viewFields);
    }

    private function _scaffoldExtendedFieldList($instance, $fieldsets,
            $associations, $modelClass) {
        $b = '';

        foreach ($fieldsets as $fieldset) {
            $fieldSetResult = $this->_scaffoldExtendedViewFieldListFieldset($fieldset, compact('instance', 'associations', 'modelClass'));
            if ($fieldSetResult) {
                $b .= $fieldSetResult;
            }
        }

        return $b;
    }

    private function _scaffoldExtendedViewFieldListFieldset(\ExtendedFieldSet $fieldset, 
            $scaffoldVars) {
        return $fieldset->getListAssociation() ?
                $this->ListFieldSet->fieldSet($fieldset, $scaffoldVars, $this->settings['listSettings']) :
                $this->ExtendedFieldSet->fieldSet($fieldset, $scaffoldVars);
    }

    private function _getCurrentController() {
        return $this->CakeLayers->getController();
    }

    private function _extractFieldData($label, $value, $type) {
        $fieldName = null;
        if (is_array($label)) {
            $labelArray = $label;
            foreach (array('label', 'fieldName', 'value', 'type') as $option) {
                if (!empty($labelArray[$option])) {
                    ${$option} = $labelArray[$option];
                }
            }
        }

        if (empty($type)) {
            $type = ViewUtilHelper::VALUE_TYPE_UNKNOWN;
        }

        if ($type == ViewUtilHelper::VALUE_TYPE_UNKNOWN && !empty($fieldName)) {
            if (($fieldInfo = $this->_getFieldInfo($fieldName))) {
                if ($fieldInfo['type'] == 'boolean') {
                    $type = ViewUtilHelper::VALUE_TYPE_BOOLEAN;
                }
            }
        }

        return compact('label', 'fieldName', 'value', 'type');
    }

    private function _getFieldInfo($fieldName) {
        $schema = $this->_getCurrentController()->{$this->_getCurrentController()->modelClass}->schema();

        if (!empty($schema[$fieldName])) {
            return $schema[$fieldName];
        } else {
            return false;
        }
    }

}
