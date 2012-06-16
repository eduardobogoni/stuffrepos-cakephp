<?php

class ExtendedFormHelper_ExtendedFieldSet {

    private $fieldsetData;
    /**     
     * @var ExtendedFormHelper
     */
    private $parent;

    public function __construct(ExtendedFormHelper $parent, $fieldsetData, $blacklist) {
        $this->parent = $parent;
        $this->fieldsetData = $fieldsetData;
        $this->blacklist = $blacklist;
    }

    public function output() {
        $linesOut = '';
        foreach ($this->fieldsetData['lines'] as $line) {
            $linesOut .= $this->_extendedInputsLine($line);
        }

        if (empty($linesOut)) {
            return '';
        } else {
            $b = '<fieldset>';

            if (!empty($this->fieldsetData['legend'])) {
                $b .= "<legend>{$this->fieldsetData['legend']}</legend>";
            }

            $b .= $linesOut;

            $b .= '</fieldset>';

            return $b;
        }
    }

    private function _extendedInputsLine($line) {
        $fieldsOut = '';
        foreach ($line as $fieldData) {
            if (!in_array($fieldData['name'], $this->blacklist)) {
                if (($width = $this->_extendedInputWidth($fieldData['name']))) {

                    $fieldData['options']['style'] = "width: $width";
                }
                $fieldsOut .= "\t" . $this->parent->input($fieldData['name'], $fieldData['options']) . "\n";
            }
        }
        if (!empty($fieldsOut)) {
            return "<div class='line'>$fieldsOut</div>";
        } else {
            return '';
        }
    }

    private function _extendedInputWidth($field) {
        if ($this->parent->isListable($field)) {
            return 'auto';
        }

        if ($this->parent->isVirtualField($field)) {
            $value = $this->parent->value();
            return strlen($value['value']) . 'em';
        }

        $fieldInfo = $this->parent->getFieldInfo($field);

        switch ($fieldInfo['type']) {
            case 'string':
                if (empty($fieldInfo['length'])) {
                    return 'auto';
                } elseif ($fieldInfo['length'] <= 16) {
                    return $fieldInfo['length'] . 'em';
                } elseif ($fieldInfo['length'] <= 32) {
                    return '16em';
                } else if ($fieldInfo['length'] <= 64) {
                    return '32em';
                } else if ($fieldInfo['length'] <= 128) {
                    return '48em';
                } else {
                    return '64em';
                }

            case 'date':
                return '8em';

            default:
                return 'auto';
        }
    }

}

?>
