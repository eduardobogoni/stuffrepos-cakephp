<?php

App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::import('Lib', 'Base.ModelTraverser');
App::import('Lib', 'Base.Basics');
App::uses('ExtendedField', 'ExtendedScaffold.View/Helper/ViewUtil');
App::uses('ExtendedLine', 'ExtendedScaffold.View/Helper/ViewUtil');
App::uses('ViewUtilExtendedFieldset', 'ExtendedScaffold.View/Helper/ViewUtil');
App::uses('ViewUtilListFieldset', 'ExtendedScaffold.View/Helper/ViewUtil');

class ViewUtilHelper extends AppHelper {
    const VALUE_TYPE_UNKNOWN = 'unknown';
    const VALUE_TYPE_BOOLEAN = 'boolean';

    public $helpers = array(
        'Html',
        'AccessControl.AccessControl',
        'Base.CakeLayers',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.Lists',
        'ExtendedScaffold.ViewUtilExtendedFieldset',
    );
    /**
     * @var AppControler
     */
    private $controller;
    /**
     * @var int
     */
    private $viewFieldCount = 0;
    
    /**
     *
     * @var array
     */
    public $settings = array(
        'listSettings' => array(),
    );
    
    /**
     *
     * @var NumberFormatter
     */
    private $moneyFormatter;
    
    public function __construct(\View $View, $settings = array()) {
        parent::__construct($View, $settings);       
        $this->moneyFormatter = NumberFormatter::create('pt_BR', NumberFormatter::DECIMAL);
        $this->moneyFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $this->moneyFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    }

    private function _getCurrentController() {
        if (empty($this->controller)) {
            App::import('Controller', $this->params['controller']);
            $controllerClass = Inflector::camelize($this->params['controller'] . '_controller');
            $this->controller = new $controllerClass;
            $this->controller->constructClasses();
        }

        return $this->controller;
    }

    /**
     *
     * @param mixed $label Uma string com o rótulo a ser usado ou um array com 
     * as seguintes opções: fieldName, value, label, type
     * @param mixed $value
     * @param mixed $valueType
     * @return string 
     */
    public function viewField($label, $value = null, $valueType = self::VALUE_TYPE_UNKNOWN) {
        $class = null;
        if ($this->viewFieldCount++ % 2 == 0) {
            $class = ' class="altrow"';
        }

        $field = $this->_extractFieldData($label, $value, $valueType);

        switch ($field['type']) {
            case self::VALUE_TYPE_BOOLEAN:
                $value = $this->yesNo($field['value']);
                break;
            case self::VALUE_TYPE_UNKNOWN:
            default:
                $value = $this->autoFormat($field['value']);
        }

        $buffer = "<tr{$class}>";
        $buffer .= "\t\t<th>" . __(Inflector::humanize($field['label']), true) . "</th>\n";
        $buffer .= "\t\t<td>\n\t\t\t" . $value . "\n&nbsp;\t\t</td>\n";
        $buffer .= "</tr>";

        return $buffer;
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
            $type = self::VALUE_TYPE_UNKNOWN;
        }

        if ($type == self::VALUE_TYPE_UNKNOWN && !empty($fieldName)) {
            if (($fieldInfo = $this->_getFieldInfo($fieldName))) {
                if ($fieldInfo['type'] == 'boolean') {
                    $type = self::VALUE_TYPE_BOOLEAN;
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
        }
        else {
            $modelClass = $options['modelClass'];
        }

        if (empty($options['associations'])) {
            $associations = $this->CakeLayers->getModelAssociations($modelClass);
        }
        else {
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

    private function _scaffoldCommonFieldList($instance, $fields, $associations, $modelClass) {
        $i = 0;
        $viewFields = array();
        foreach ($fields as $_field) {

            $isKey = false;

            if (!empty($associations['belongsTo'])) {
                foreach ($associations['belongsTo'] as $_alias => $_details) {
                    if ($_field === $_details['foreignKey']) {
                        $isKey = true;
                        $viewFields[] = array(
                            'label' => __(Inflector::humanize($_alias), true),
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
                    'label' => __(Inflector::humanize($_field), true),
                    'value' => ModelTraverser::value($this->_getCurrentController()->{$modelClass}, $instance, $_field),
                    'fieldName' => $_field,
                );
            }
        }

        return $this->commonViewFieldList($viewFields);
    }

    private function _scaffoldExtendedFieldList($instance, $fieldsets, $associations, $modelClass) {
        $b = '';

        foreach ($fieldsets as $fieldset) {
            $b .= $this->_scaffoldExtendedViewFieldListFieldset($fieldset, compact('instance', 'associations', 'modelClass'));
        }

        return $b;
    }

    private function _scaffoldExtendedViewFieldListFieldset($fieldset, $scaffoldVars) {
        if (empty($fieldset['listAssociation'])) {
            return $this->ViewUtilExtendedFieldset->fieldSet($fieldset, $scaffoldVars);
        } else {
            $f = new ViewUtilListFieldset($this, $fieldset, $scaffoldVars, $this->settings['listSettings']);
        }

        return $f->output();
    }
    
    public function date($value) {
        return $this->_isDate($value);
    }

    private function _isDate($value) {
        if (($timestamp = strtotime(strval($value))) && preg_match('/\d/', strval($value))) {
            $dateOnly = date('Y-m-d', $timestamp);
            $dateOnlyTimestamp = strtotime($dateOnly);
            if ($dateOnlyTimestamp == $timestamp) {
                return date('d/m/Y', $timestamp);
            } else {
                return date('d/m/Y G:i', $timestamp);
            }
        } else if (is_array($value) && isset($value['month']) && isset($value['day']) && isset($value['year'])) {
            $timestamp = mktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
            return date('d/m/Y', $timestamp);
        } else if (is_array($value) && isset($value['month']) && isset($value['year'])) {
            $timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
            return date('m/Y', $timestamp);
        } else {
            return false;
        }
    }

    private function _isDecimal($value) {
        return preg_match('/^\d+(\.\d+)?$/', strval($value));
    }

    public function yesNo($value) {        
        if ($value) {
            return __('Yes', true);
        } else {
            return __('No', true);
        }
    }
    
    public function money($value) {        
        if (!is_float($value)) {
            $value = floatval($value);
        }
        return $this->moneyFormatter->format($value);
    }

    public function decimal($value) {
        return str_replace('.', ',', strval($value));
    }

    public function autoFormat($value) {
        if ($this->_isDecimal($value)) {
            return $this->decimal($value);
        } else if (($formated = $this->_isDate($value)) !== false) {
            return $formated;
        } else {
            return $this->string($value);
        }
    }

    /**
     *
     * @param string $string
     * @param string $mask
     * @return string
     */
    public function stringMask($string, $mask) {
        $stringLength = strlen($string);
        $maskLength = strlen($mask);

        $s = 0;
        $m = 0;
        $b = '';

        while ($m < $maskLength) {
            if ($m < $maskLength) {
                if (in_array($mask[$m], array('a', '9', '*'))) {
                    if ($s < $stringLength) {
                        $b .= $string[$s];
                        $s++;
                    }
                    else {
                        $b .= '_';
                    }
                    
                }
                else {
                    $b .= $mask[$m];
                }
                $m++;
            }
        }

        return $b;
    }
    
    public function string($value) {
        return nl2br(strip_tags($value));
    }

}
