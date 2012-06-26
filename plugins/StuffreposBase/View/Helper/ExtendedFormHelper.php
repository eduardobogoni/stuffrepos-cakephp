<?php

App::import('Helper', 'Form');
App::import('Lib', 'StuffreposBase.ExtendedFieldsParser');
App::import('Lib', 'StuffreposBase.ArrayUtil');
require_once(dirname(__FILE__) . '/ExtendedFormHelper/ExtendedFormHelper_ExtendedFieldSet.php');
require_once(dirname(__FILE__) . '/ExtendedFormHelper/ExtendedFormHelper_InputSearchable.php');
require_once(dirname(__FILE__) . '/ExtendedFormHelper/ExtendedFormHelper_InputsOnSubmit.php');
require_once(dirname(__FILE__) . '/ExtendedFormHelper/ExtendedFormHelper_ListFieldSet.php');

class ExtendedFormHelper extends FormHelper {

    const FIXED_PREFIX = '_fixed';
    const MASKED_SUFFIX = '_masked';

    public $helpers = array(
        'Html',
        'StuffreposBase.Lists',
        'StuffreposBase.CakeLayers',
        'StuffreposBase.ScaffoldUtil',
    );
    private $inputsOnSubmit;
    private $formId;

    public function __construct(View $View, $settings = array()) {
        parent::__construct($View, $settings);
        $this->inputsOnSubmit = new ExtendedFormHelper_InputsOnSubmit($this);
    }
    
    public function beforeLayout($layoutFile) {
        parent::beforeLayout($layoutFile);        
        $this->ScaffoldUtil->addJavascriptLink('dom_helper.js');
        $this->ScaffoldUtil->addJavascriptLink('lang.js');
        $this->ScaffoldUtil->addJavascriptLink('collections.js');
        $this->ScaffoldUtil->addJavascriptLink('extended_form_helper.js');
        $this->ScaffoldUtil->addJavascriptLink('extended_form_helper__input_searchable.js');
        $this->ScaffoldUtil->addJavascriptLink('jquery.textchange.min.js');
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
        if (empty($fieldset['listAssociation'])) {
            $f = new ExtendedFormHelper_ExtendedFieldSet($this, $fieldset, $blacklist);
        } else {
            $f = new ExtendedFormHelper_ListFieldset($this, $fieldset, $blacklist);
        }

        return $f->output();
    }

    public function input($fieldName, $options = array()) {
        $fields = array();
        $this->setEntity($fieldName);
        $view = & ClassRegistry::getObject('view');

        if (strpos($fieldName, '.') === false) {
            $fieldName = $this->model() . '.' . $fieldName;
        }

        if ($this->isFixed($fieldName)) {
            setByArray($this->data[self::FIXED_PREFIX], fieldName2Array($fieldName), true);
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
        $view = & ClassRegistry::getObject('view');
        return $view->getVar($this->getListVariableDefaultName($fieldName)) !== null;
    }

    public function getListVariableDefaultName($fieldName) {
        $fieldNameParts = explode('.', $fieldName);

        return Inflector::variable(
                        Inflector::pluralize(
                                preg_replace('/(?:_id)$/', '', $fieldNameParts[count($fieldNameParts) - 1])
                        ));
    }

    public function getReadonlyListOptions($fieldName) {
        $view = & ClassRegistry::getObject('view');
        $fieldValue = getByArray($view->data, explode('.', $fieldName));
        return array(
            $fieldValue => $view->viewVars[$this->getListVariableDefaultName($fieldName)][$fieldValue]
        );
    }

    public function inputHiddenAllData() {
        $buffer = "";
        foreach (array_keys(array2NamedParams($this->data)) as $field) {
            $buffer .= $this->input($field, array('type' => 'hidden'));
        }
        return $buffer;
    }

    public function isFixed($fieldName) {
        App::import('Lib', 'Stuffrepos.ArrayUtil');
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
        $hiddenId = $this->createNewDomId();
        $hiddenAttributes = array('id' => $hiddenId);
        if (isset($attributes['value'])) {
            $hiddenAttributes['value'] = $attributes['value'];
        }
        $hiddenInput = $this->hidden($fieldName, $hiddenAttributes);
        $visibleInputName = $fieldName . '_masked';
        $this->setEntity($visibleInputName);
        $visibleId = $this->createNewDomId();
        $visibleInput = parent::text(
                        $visibleInputName, array_merge(
                                $attributes, array(
                            'id' => $visibleId
                                )
                        )
        );
        $buffer = $hiddenInput . $visibleInput . $this->dateTimeJavascript($hiddenId, $visibleId);
        $this->setEntity($fieldName);
        $this->inputsOnSubmit->addInput('dateTime', $visibleId, $hiddenId);
        return $buffer;
    }

    private function dateTimeJavascript($hiddenId, $visibleId) {
        return $this->javascriptTag(
                        "\$(document).ready(function(){
   \$('#$visibleId').inputmask('d/m/y');  //direct mask   
                if (\$('#$hiddenId').val()) {                
                    try {
                        date = $.datepicker.parseDate('yy-mm-dd',\$('#$hiddenId').val());
                    }
                    catch(ex) {
                        date = null;
                    }
                    
                    if (date instanceof Date) {
                        \$('#$visibleId').val(
                            $.datepicker.formatDate('dd/mm/yy',date)
                        );
                    }
                
                }
});");
    }

    public function text($fieldName, $options = array()) {
        if (!empty($options['mask'])) {
            $hiddenId = $this->createNewDomId();
            $b = $this->hidden($fieldName, array('id' => $hiddenId));

            $visibleInputName = $fieldName . '_masked';
            $this->setEntity($visibleInputName);
            $visibleId = $this->createNewDomId();
            $b .= parent::text(
                            $visibleInputName, array_merge(
                                    $options, array(
                                'id' => $visibleId
                                    )
                            )
            );

            $b .= $this->_maskedTextOnDocumentReady(
                    $hiddenId, $visibleId, $options['mask']
            );

            $this->inputsOnSubmit->addInput('text', $visibleId, $hiddenId);

            return $b;
        } else if (!empty($options['search'])) {
            $input = new ExtendedFormHelper_InputSearchable($this, $fieldName, $options);
            return $input->output();
        } else {
            return parent::text($fieldName, $options);
        }
    }

    private function _maskedTextOnDocumentReady($hiddenId, $visibleId, $mask) {
        return $this->javascriptTag(
                        "\$(document).ready(function(){
   \$('#$visibleId').inputmask({'mask': '$mask', 'autoUnmask': true});
   \$('#$visibleId').inputmask('setvalue',\$('#$hiddenId').val());            
});");
    }

    public function checkbox($fieldName, $options = array()) {
        if (!empty($options['readonly'])) {
            $options['disabled'] = "disabled";
        }
        return parent::checkbox($fieldName, $options);
    }

    public function create($model = null, $options = array()) {
        $this->formId = $this->createNewDomId();
        $options['id'] = $this->formId;
        $buffer = parent::create($model, $options);
        return $buffer;
    }

    public function end($options = null) {
        return parent::end($options) . $this->onSubmitEvent();
    }

    private function onSubmitEvent() {
        $b = "\t\$('#{$this->formId}').submit(function(){\n";
        $b .= $this->inputsOnSubmit->outputJavascript();
        $b .= "
            return true;
});";
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

    private function _currentUrl() {
        $pattern = '/^' . str_replace('/', '\/', $this->base) . '/';
        return preg_replace($pattern, '', $this->here);
    }

}

?>