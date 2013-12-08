<?php

class InputsOnSubmit {
    
    /**
     *
     * @var ExtendedFormHelper 
     */
    private $parent;
    
    /**
     *
     * @var array
     */
    private $inputs = array();

    public function __construct(ExtendedFormHelper $parent) {
        $this->parent = $parent;
    }

    public function addInput($type, $visibleInputId, $hiddenInputId) {
        $this->inputs[] = compact('type','visibleInputId','hiddenInputId');
    }
    
    public function outputJavascript() {
        $b = '';
        foreach($this->inputs as $input) {
            $b .= $this->_inputOnSubmitEvent($input);
        }
        return $b;
    }

    private function _inputOnSubmitEvent($input) {
        switch ($input['type']) {
            case 'text':
                return $this->_maskedTextOnSubmit($input);

            case 'date':
            case 'time':
            case 'datetime':
                return DateTimeInput::onSubmit($input);

            default:
                throw new Exception("Type not mapped: \"{$input['type']}\".");
        }
    }
    
    private function _maskedTextOnSubmit($input) {
        return <<<EOT
        {
            var text = \$('#{$input['visibleInputId']}').val();            
            \$('#{$input['hiddenInputId']}').inputmask('setvalue',text);            
        }
EOT;
    }
}

?>
