<?php

App::uses('AppHelper', 'View/Helper');

class DetailHelper extends AppHelper {

    public $helpers = array(        
        'AccessControl.AccessControl',
        'Base.CakeLayers',
        'ExtendedScaffold.Lists',
        'ExtendedScaffold.ViewUtilExtendedFieldset',
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
                            $instance, ExtendedFieldsParser::parseFieldsets($fields), $associations, $modelClass
            );
        } else {
            return $this->_scaffoldCommonFieldList($instance, $fields, $associations, $modelClass);
        }
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
            $b .= $this->_scaffoldExtendedViewFieldListFieldset($fieldset, compact('instance', 'associations', 'modelClass'));
        }

        return $b;
    }

    private function _scaffoldExtendedViewFieldListFieldset($fieldset,
            $scaffoldVars) {
        if (empty($fieldset['listAssociation'])) {
            return $this->ViewUtilExtendedFieldset->fieldSet($fieldset, $scaffoldVars);
        } else {
            $f = new ViewUtilListFieldset($this, $fieldset, $scaffoldVars, $this->settings['listSettings']);
        }

        return $f->output();
    }

}
