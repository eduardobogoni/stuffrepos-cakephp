<?php

App::uses('FormHelper', 'View/Helper');
App::uses('ExtendedFieldsParser', 'Base.Lib');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('Basics', 'Base.Lib');
App::uses('ExtendedFieldSet', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputSearchable', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputsOnSubmit', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('ListFieldSet', 'ExtendedScaffold.View/Helper/ExtendedForm');
App::uses('InputMasked', 'ExtendedScaffold.View/Helper/ExtendedForm');

class ExtendedFormHelper extends FormHelper {

    const FIXED_PREFIX = '_fixed';
    const MASKED_SUFFIX = '_masked';

    public $helpers = array(
        'Html',
        'ExtendedScaffold.Lists',
        'Base.CakeLayers',
        'ExtendedScaffold.ScaffoldUtil',
    );
    private $inputsOnSubmit;
    private $formId;

    public function __construct(View $View, $settings = array()) {
        parent::__construct($View, $settings);
        $this->inputsOnSubmit = new InputsOnSubmit($this);
    }
    
    public function beforeLayout($layoutFile) {
        parent::beforeLayout($layoutFile);        
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.dom_helper.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.lang.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.collections.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.extended_form_helper.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.extended_form_helper__input_masked.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.extended_form_helper__input_searchable.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.extended_form_helper__list_field_set.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery.textchange.min.js');
        $this->ScaffoldUtil->addJavascriptLink('ExtendedScaffold.jquery.inputmask/jquery.inputmask.js');
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
        if (empty($fieldset['listAssociation'])) {
            $f = new ExtendedFieldSet($this, $fieldset, $blacklist);
        } else {
            $f = new ListFieldSet($this, $fieldset, $blacklist);
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
        $view = & ClassRegistry::getObject('view');
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
        $hiddenId = $this->createNewDomId();
        $hiddenAttributes = array('id' => $hiddenId);
        if (isset($selected['value'])) {
            $hiddenAttributes['value'] = $selected['value'];
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
        $this->formId = $this->createNewDomId();
        $options['id'] = $this->formId;
        $buffer = parent::create($model, $options);
        return $buffer;
    }

    public function end($options = null) {
        return parent::end($options) . $this->onSubmitEvent() . $this->_onReadyEvent();
    }

    private function onSubmitEvent() {
        $b = "\t\$('#{$this->formId}').submit(function(){\n";
        $b .= $this->inputsOnSubmit->outputJavascript();
        $b .= "
            return true;
});";
        return $this->javascriptTag($b);
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
