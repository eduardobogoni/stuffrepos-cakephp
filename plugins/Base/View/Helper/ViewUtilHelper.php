<?php

App::import('Lib', 'StuffreposBase.ExtendedFieldsParser');
App::import('Lib', 'StuffreposBase.ModelTraverser');
App::import('Lib', 'StuffreposBase.Basics');

class ViewUtilHelper extends AppHelper {
    const VALUE_TYPE_UNKNOWN = 'unknown';
    const VALUE_TYPE_BOOLEAN = 'boolean';

    public $helpers = array('Html', 'AccessControl', 'CakeLayers', 'Lists');
    /**
     * @var AppControler
     */
    private $controller;
    /**
     * @var int
     */
    private $viewFieldCount = 0;

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
            $f = new ViewUtilHelper_ExtendedFieldset($this, $fieldset, $scaffoldVars);
        } else {
            $f = new ViewUtilHelper_ListFieldset($this, $fieldset, $scaffoldVars);
        }

        return $f->output();
    }

    private function _isDate($value) {
        if ($timestamp = strtotime(strval($value))) {
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

    public function decimal($value) {
        return str_replace('.', ',', strval($value));
    }

    public function autoFormat($value) {
        if ($this->_isDecimal($value)) {
            return $this->decimal($value);
        } else if (($formated = $this->_isDate($value)) !== false) {
            return $formated;
        } else {
            return $value;
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

}

class ViewUtilHelper_ExtendedFieldset {

    private $legend;
    private $lines = array();
    private $scaffoldVars;
    private $parent;

    public function __construct(ViewUtilHelper $parent, $data, &$scaffoldVars) {
        $this->parent = $parent;
        $this->legend = empty($data['legend']) ? null : $data['legend'];
        foreach ($data['lines'] as $lineData) {
            $this->lines[] = new ViewUtilHelper_ExtendedLine($this, $lineData);
        }
        $this->scaffoldVars = &$scaffoldVars;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getInstance() {
        return $this->scaffoldVars['instance'];
    }

    public function getAssociations() {
        return $this->scaffoldVars['associations'];
    }

    public function getModelClass() {
        return $this->scaffoldVars['modelClass'];
    }

    public function getLinesFieldCountLcd() {
        $previous = 1;
        foreach ($this->lines as $line) {
            $lcd = Basics::lcd($previous, $line->getFieldCount());
        }
        return $lcd;
    }

    public function output() {
        $b = '';
        if (!empty($this->legend)) {
            $b .= "<h3>{$this->legend}</h3>";
        }

        $b .= '<table class="viewFieldList">';
        $lineCount = 0;

        foreach ($this->lines as $line) {
            $b .= $line->output($lineCount++ % 2 == 0);
        }
        $b .= '</table>';

        return $b;
    }

    public function getFieldLabelWidth() {
        // Largura da tabela
        $tableWidth = 1;

        // Quantidade de campos da maior linha
        $maxLineFieldCount = $this->_getMaxLineFieldCount();

        // Largura de cada campo
        $fieldWidth = $tableWidth / $maxLineFieldCount;

        // Largura do label
        $labelWidth = $fieldWidth * ViewUtilHelper_ExtendedField::LABEL_WIDTH_PERCENT;

        // Formato para CSS
        $result = number_format($labelWidth * 100, 0) . '%';

        return $result;
    }

    public function getFieldInputWidth() {
        // Largura da tabela
        $tableWidth = 1;

        // Quantidade de campos da maior linha
        $maxLineFieldCount = $this->_getMaxLineFieldCount();

        // Largura de cada campo
        $fieldWidth = $tableWidth / $maxLineFieldCount;

        // Largura do label
        $inputWidth = $fieldWidth * (1 - ViewUtilHelper_ExtendedField::LABEL_WIDTH_PERCENT);

        // Formato para CSS
        $result = number_format($inputWidth * 100, 0) . '%';

        return $result;
    }

    private function _getMaxLineFieldCount() {
        $counts = array();
        foreach ($this->lines as $line) {
            $counts[] = $line->getFieldCount();
        }
        return max($counts);
    }

}

class ViewUtilHelper_ExtendedLine {

    private $fields = array();
    /**
     *
     * @var ViewUtilHelper_ExtendedFieldset
     */
    private $parent;

    public function __construct(ViewUtilHelper_ExtendedFieldset $parent, $data) {
        $this->parent = $parent;
        foreach ($data as $fieldData) {
            $this->fields[] = new ViewUtilHelper_ExtendedField($this, $fieldData);
        }
    }

    public function getParent() {
        return $this->parent;
    }

    public function getFieldInputColspan() {
        // Quantidade de células da linha
        $cellCount = $this->parent->getLinesFieldCountLcd();
        // Quantidade de células para valores
        $valuesCellCount = $cellCount - $this->getFieldCount();

        return $valuesCellCount / $this->getFieldCount();
        //return 1;
    }

    public function getFieldCount() {
        return count($this->fields);
    }

    public function output($altrow) {
        $b = '';

        $class = null;
        if ($altrow) {
            $class = ' class="altrow"';
        }

        $b = "<tr $class>";

        foreach ($this->fields as $field) {
            $b .= $field->output();
        }
        $b .= '</tr>';


        return $b;
    }

}

class ViewUtilHelper_ExtendedField {
    const LABEL_WIDTH_PERCENT = 0.4;
    private $name;
    private $options;
    /**
     *
     * @var ViewUtilHelper_ExtendedLine
     */
    private $parent;

    public function __construct(ViewUtilHelper_ExtendedLine $parent, $data) {
        $this->parent = $parent;
        $this->name = $data['name'];
        $this->options = $data['options'];
    }

    public function output() {
        $b = '';

        $b .= '<th style="width: ' . $this->parent->getParent()->getFieldLabelWidth() . "\">\n";
        $b .= $this->_getLabel() . ':';
        $b .= "\n</th>\n";

        $b .= '<td';
        $b .= ' style="width: ' . $this->_getInputWidth() . '"';
        $b .= ' colspan="' . $this->_getInputColspan() . '"';
        $b .= ">\n";
        $b .= $this->_getValue();
        $b .= "\n</td>\n";

        return $b;
    }

    /**
     *
     * 
     * @param type $currentLineFieldCount
     * @param type $linesMaxFieldCount
     * @return type 
     */
    private function _getInputColspan() {
        return $this->parent->getFieldInputColspan();
    }

    private function _getInputWidth() {
        return $this->parent->getParent()->getFieldInputWidth();
    }

    private function _getLabel() {
        if (!empty($this->options['label'])) {
            return $this->options['label'];
        } else {
            $associations = &$this->parent->getParent()->getAssociations();

            if (!empty($associations['belongsTo'])) {
                foreach ($associations['belongsTo'] as $_alias => $_details) {
                    if ($this->name === $_details['foreignKey']) {
                        return __($_alias, true);
                    }
                }
            }
            return __(Inflector::humanize($this->_getFieldName()), true);
        }
    }

    private function _getType() {
        $fieldInfo = $this->parent->getParent()->getParent()->CakeLayers->getFieldSchema(
                $this->_getFieldName(), $this->_getModelClass()
        );
        if (empty($fieldInfo['type'])) {
            return false;
        } else {
            return $fieldInfo['type'];
        }
    }

    private function _getModelClass() {
        $nameParts = Basics::fieldNameToArray($this->name);
        if (count($nameParts) > 1) {
            return $nameParts[0];
        } else {
            return $this->parent->getParent()->getModelClass();
        }
    }

    private function _getFieldName() {
        $nameParts = Basics::fieldNameToArray($this->name);
        if (count($nameParts) > 1) {
            return $nameParts[1];
        } else {
            return $nameParts[0];
        }
    }

    private function _getPath() {
        $path = explode('.', $this->name);
        if (count($path) == 1) {
            return array(
                $this->parent->getParent()->getModelClass(),
                $path[0]
            );
        }
        else {
            return $path;
        }
    }

    private function _mask() {
        return empty($this->options['mask']) ? null : $this->options['mask'];
    }

    private function _getValue() {

        $ViewUtilHelper = &$this->parent->getParent()->getParent();

        $instance = &$this->parent->getParent()->getInstance();
        $associations = &$this->parent->getParent()->getAssociations();

        if (!empty($associations['belongsTo'])) {
            foreach ($associations['belongsTo'] as $_alias => $_details) {
                if ($this->name === $_details['foreignKey']) {
                    return $ViewUtilHelper->AccessControl->linkOrText(
                                    $instance[$_alias][$_details['displayField']], array(
                                'controller' => $_details['controller'],
                                'action' => 'view',
                                $ViewUtilHelper->autoFormat($instance[$_alias][$_details['primaryKey']])));
                }
            }
        }

        $fieldType = $this->_getType();
        $value = $ViewUtilHelper->CakeLayers->modelInstanceFieldByPath(
                $this->parent->getParent()->getModelClass()
                , $instance
                , $this->_getPath()
                , true
        );

        switch ($fieldType) {
            case 'boolean':
                return $ViewUtilHelper->yesNo($value);

            case 'string':
                if ($this->_mask()) {
                    return $ViewUtilHelper->stringMask($value, $this->_mask());
                } else {
                    return $value;
                }

            case 'float':
                return $ViewUtilHelper->decimal($value);

            default:
                return $ViewUtilHelper->autoFormat($value);
        }
    }

}

class ViewUtilHelper_ListFieldset {

    private $legend;
    private $fields = array();
    private $scaffoldVars;
    private $parent;
    private $listAssociation;

    public function __construct(ViewUtilHelper $parent, $data, &$scaffoldVars) {
        $this->parent = $parent;
        $this->legend = empty($data['legend']) ? null : $data['legend'];
        foreach ($data['lines'] as $fields) {
            foreach ($fields as $field) {
                $this->fields[$field['name']] = $field['options'];
            }
        }
        $this->listAssociation = $data['listAssociation'];
        $this->scaffoldVars = &$scaffoldVars;
    }

    public function output() {
        $b = '';
        if (!empty($this->legend)) {
            $b .= "<h3>{$this->legend}</h3>";
        }

        $b .= $this->parent->Lists->rowsTable(
                $this->fields
                , $this->_rows()
                , array(
            'model' => $this->_model()
            , 'showActions' => false
                    , 'controller' => $this->_controller()
                )
        );

        return $b;
    }

    private function _rows() {
        return ModelTraverser::value(
                        $this->parent->CakeLayers->getModel($this->scaffoldVars['modelClass'])
                        , $this->scaffoldVars['instance']
                        , $this->listAssociation
        );
    }

    private function _model() {
        return $this->parent->CakeLayers->modelAssociationModel(
                        $this->scaffoldVars['modelClass']
                        , $this->listAssociation
                        , true
                );
    }
    
    private function _controller() {
        $controller = $this->parent->CakeLayers->getController(
                Inflector::pluralize($this->_model()->name), false
                );
        if ($controller) {
            return $controller->name;
        }
        else {
            return null;
        }
    }

}

?>
