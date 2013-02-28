<?php

class InputMasked {

    public function __construct(ExtendedFormHelper $parent, $fieldName, $options) {
        $this->parent = $parent;
        $this->fieldName = $fieldName;
        $this->options = $options;
        $this->hiddenInputId = $this->parent->createNewDomId();
        $this->visibleInputId = $this->parent->createNewDomId();
    }

    public function output() {
        $b = $this->_hiddenInput();
        $b .= $this->_visibleInput();
        $b .= $this->_maskedTextOnDocumentReady();

        $this->parent->getInputsOnSubmit()->addInput('text', $this->visibleInputId, $this->hiddenInputId);

        return $b;
    }

    private function _hiddenInput() {
        return $this->parent->hidden($this->fieldName, array('id' => $this->hiddenInputId));
    }

    private function _visibleInput() {
        $visibleInputName = $this->fieldName . '_masked';
        $this->parent->setEntity($visibleInputName);
        $visibleOptions = $this->options;
        unset($visibleOptions['mask']);
        return $this->parent->text(
                        $visibleInputName, array_merge(
                                $visibleOptions, array(
                            'id' => $this->visibleInputId,
                                    'initCallback' => 'ExtendedFormHelper.InputMasked.initCallback'
                                )
                        )
        );
    }

    private function _maskedTextOnDocumentReady() {
        return $this->parent->javascriptTag(
                        "\$(document).ready(function(){
   \$('#{$this->visibleInputId}').inputmask({'mask': '{$this->options['mask']}', 'autoUnmask': true});
   \$('#{$this->visibleInputId}').inputmask('setvalue',\$('#{$this->hiddenInputId}').val());            
});");
    }

}

?>
