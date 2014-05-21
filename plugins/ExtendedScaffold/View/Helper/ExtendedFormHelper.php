<?php

App::uses('FormHelper', 'View/Helper');
App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('Basics', 'Base.Lib');
App::uses('DateTimeInput', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('ExtendedFieldSet', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputSearchable', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputsOnSubmit', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('ListFieldSet', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputMasked', 'ExtendedScaffold.View/Helper/ExtendedForm');

class ExtendedFormHelper extends FormHelper {

    const FIXED_PREFIX = '_fixed';
    const MASKED_SUFFIX = '_masked';

    public $helpers = array(
        'AccessControl.AccessControl',
        'Html',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.Lists',
        'Base.CakeLayers',
        'ExtendedScaffold.ScaffoldUtil',
    );
    public $inputsOnSubmit;
    private $formId;

    public function __construct(View $View, $settings = array()) {
        parent::__construct($View, $settings);
        $this->inputsOnSubmit = new InputsOnSubmit($this);
    }
    
    public function beforeLayout($layoutFile) {
        parent::beforeLayout($layoutFile);        
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.DomHelper.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.Lang.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.Collections.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.ExtendedFormHelper.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.ExtendedFormHelper/InputMasked.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.ExtendedFormHelper/InputSearchable.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.ExtendedFormHelper/ListFieldSet.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.ExtendedFormHelper/DateTimeInput.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery-1.8.3.min.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery-ui-1.10.4.custom.min.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery.textchange.min.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery.inputmask/jquery.inputmask.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.moment.min.js');
        $this->ScaffoldUtil->addCssLink('ExtendedScaffold.jquery-ui-1.10.4.custom.min.css');
    }

    public function defaultForm($fields = null) {
        $blacklist = array('created', 'modified', 'updated', 'canceled');
        if (ExtendedFieldsParser::isExtendedFieldsDefinition($fields)) {
            return $this->extendedCreate() .
                    $this->extendedInputs(
                            ExtendedFieldsParser::extractExtendedFields($fields), $blacklist
                    ) . $this->extendedEnd(__('Submit', true));
        } else {
            return $this->create(null, array('url' => $this->_currentUrl())) .
                    $this->inputs($fields, $blacklist) .
                    $this->end(__('Submit', true));
        }
    }

    public function extendedCreate($model = null, $options = array()) {
        if (empty($options['class'])) {
            $options['class'] = 'extendedForm';
        }

        if (empty($options['url']) && empty($options['action'])) {
            $options['url'] = $this->_currentUrl();
        }

        $options['enctype'] = 'multipart/form-data';

        return $this->create($model, $options);
    }

    public function extendedInputs($data, $blacklist = array(), $defaultModel = null) {
        $b = "\n<div class='scaffoldInputsLine'>\n";
        foreach (ExtendedFieldsParser::parseFieldsets($data, $defaultModel) as $fieldset) {

            $fieldsetOut = $this->_extendedInputsFieldset(
                    $fieldset, $blacklist
            );

            if (!empty($fieldsetOut)) {
                $b .= $fieldsetOut;
            }
        }
        $b .= "\n</div>\n";

        return $b;
    }

    public function extendedEnd($options = null) {
        return $this->end($options);
    }

    public function getFieldInfo($field) {
        if (empty($this->fieldset[$this->model()]['fields'][$field])) {
            return false;
        } else {
            return $this->fieldset[$this->model()]['fields'][$field];
        }
    }

    private function _extendedInputsFieldset($fieldset, $blacklist) {
        $currentModel = $this->model();
        if (empty($fieldset['listAssociation'])) {
            $f = new ExtendedFieldSet($this, $fieldset, $blacklist);
        } else {
            $f = new ListFieldSet($this, $fieldset, $blacklist);
        }
        $output = $f->output();
        $this->setEntity($currentModel);        
        return $output;
    }

    public function input($fieldName, $options = array()) {
        $fields = array();
        $this->setEntity($fieldName);
        $view = ClassRegistry::getObject('view');

        if (strpos($fieldName, '.') === false) {
            $fieldName = $this->model() . '.' . $fieldName;
        }

        if ($this->isFixed($fieldName)) {
            ArrayUtil::setByArray($this->request->data[self::FIXED_PREFIX], Basics::fieldNameToArray($fieldName), true);
            $options['readonly'] = true;

            if ($this->isListable($fieldName)) {
                $options['options'] = $this->getReadonlyListOptions($fieldName);
            }
            $fields[self::FIXED_PREFIX . '.' . $fieldName] = array('type' => 'hidden');
        }

        if (!empty($options['mask']) || !empty($options['search'])) {
            $options['type'] = 'text';
        }

        $fields[$fieldName] = $options;

        return $this->_inputSubinputs($fields);
    }

    private function _inputSubinputs($fields) {
        $b = '';
        foreach ($fields as $fieldName => $options) {
            $b .= parent::input($fieldName, $options) . "\n";
        }
        return $b;
    }

    public function isListable($fieldName) {        
        return $this->_View->getVar($this->getListVariableDefaultName($fieldName)) !== null;
    }

    public function getListVariableDefaultName($fieldName) {
        $fieldNameParts = explode('.', $fieldName);

        return Inflector::variable(
                        Inflector::pluralize(
                                preg_replace('/(?:_id)$/', '', $fieldNameParts[count($fieldNameParts) - 1])
                        ));
    }

    public function getReadonlyListOptions($fieldName) {
        $view = ClassRegistry::getObject('view');
        $fieldValue = ArrayUtil::arrayIndex($view->data, explode('.', $fieldName));
        return array(
            $fieldValue => $view->viewVars[$this->getListVariableDefaultName($fieldName)][$fieldValue]
        );
    }

    public function inputHiddenAllData() {
        $buffer = "";
        foreach (array_keys(ArrayUtil::array2NamedParams($this->data)) as $field) {
            $buffer .= $this->input($field, array('type' => 'hidden'));
        }
        return $buffer;
    }

    public function isFixed($fieldName) {
        return ArrayUtil::arrayIndex(
                        $this->data, array_merge(
                                array(self::FIXED_PREFIX), explode('.', $fieldName)
                        )
                ) || $this->isVirtualField($fieldName);
    }

    public function isVirtualField($fieldName) {
        $this->setEntity($fieldName);
        $parts = explode('.', $fieldName);
        $lastPart = $parts[count($parts) - 1];
        if (App::import('Model', $this->model())) {
            $model = ClassRegistry::init($this->model());
            return !empty($model->virtualFields[$lastPart]);
        } else {
            return false;
        }
    }

    public function dateTime($fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $attributes = array()) {
        return DateTimeInput::dateTime($this, $fieldName, $dateFormat, $timeFormat, $selected, $attributes);
    }

    public function fieldDefinition($fieldName, $property = false) {
        $fieldPath = $this->_fieldDefinitionPath($fieldName);
        $fieldDef = $this->_introspectModel($fieldPath[0], 'fields', $fieldPath[1]);
        if ($property) {
            return $fieldDef[$property];
        } else {
            return $fieldDef;
        }
    }

    private function _fieldDefinitionPath($fieldName) {
        $parts = Basics::fieldPath($fieldName, $this->model());
        $path = array();
        foreach ($parts as $part) {
            if (!preg_match('/^[0-9]$/', $part) && !preg_match('/^\%.+\%$/', $part)) {
                $path[] = $part;
            }
        }
        return $path;
    }

    public function text($fieldName, $options = array()) {
        if (!empty($options['mask'])) {
            $input = new InputMasked($this,$fieldName,$options);
            return $input->output();
        } else if (!empty($options['search'])) {
            $input = new InputSearchable($this,$fieldName,$options);
            return $input->output();            
        } else {
            return parent::text($fieldName, $options);
        }
    }

    public function create($model = null, $options = array()) {
        $this->formId = empty($options['id']) ? 
                $this->createNewDomId() :
                $options['id'];
        $options['id'] = $this->formId;
        $options['onsubmit'] = 'return ExtendedFormHelper.onSubmit(this)';
        $buffer = parent::create($model, $options);
        $buffer .= $this->_refererInput();
        return $buffer;
    }
    
    private function _refererInput() {
        return $this->hidden('_ScaffoldUtil.referer');
    }

    public function end($options = null) {
        return parent::end($options) . $this->_onReadyEvent();
    }

    private function _onReadyEvent() {
        $b = <<<EOT
$(document).ready(function(){
    ExtendedFormHelper.initInputs($('#{$this->formId}'));
    });
EOT;
        return $this->javascriptTag($b);
    }

    public function javascriptTag($content) {
        return "\n<script type='text/javascript'>\n$content\n</script>\n";
    }

    public function createNewDomId() {
        $maxDigits = 8;
        $n = rand(0, pow(10, 8) - 1);
        return "id_" . str_pad($n, $maxDigits, '0', STR_PAD_LEFT);
    }

    /**
     *
     * @return InputsOnSubmit
     */
    public function getInputsOnSubmit() {
        return $this->inputsOnSubmit;
    }
    
    private function _currentUrl() {
        $pattern = '/^' . str_replace('/', '\/', $this->base) . '/';
        return preg_replace($pattern, '', $this->here);
    }

}

?>
