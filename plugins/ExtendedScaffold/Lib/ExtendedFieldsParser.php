<?php

class ExtendedFieldsParser {
    const EXTENDED_KEY = '_extended';

    public static function &getInstance() {
        static $instance = array();

        if (!$instance) {
            $instance[0] = & new ExtendedFieldsParser();
        }
        return $instance[0];
    }

    public function isExtendedFieldsDefinition($fields) {
        return isset($fields[self::EXTENDED_KEY]);
    }

    public function extractExtendedFields($fields) {
        $_this = & self::getInstance();
        if ($_this->isExtendedFieldsDefinition($fields)) {
            return $fields[self::EXTENDED_KEY];
        } else {
            throw new Exception("Parâmetro \$fields não é uma definição extendida.");
        }
    }

    public function parseFieldsets($fieldsData, $defaultModel = null) {
        $_this = & self::getInstance();

        if ($_this->isExtendedFieldsDefinition($fieldsData)) {
            $fieldsData = $_this->extractExtendedFields($fieldsData);
        }

        $fieldsets = array();

        foreach ($fieldsData as $dataKey => $dataValue) {
            $fieldsets[] = $_this->_parseFieldsetData($dataKey, $dataValue, $defaultModel);
        }
        return $fieldsets;
    }

    public function fieldInDefinition($fieldsData, $field, $defaultModel = null) {
        $_this = & self::getInstance();
        $fieldSets = $this->parseFieldsets($fieldsData);      
        
        foreach ($fieldSets as $fieldSet) {
            foreach ($fieldSet['lines'] as $line) {
                foreach ($line as $lineField) {                    
                    if (AppBasics::fieldFullName($lineField['name'], $defaultModel) == AppBasics::fieldFullName($field, $defaultModel)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function _parseFieldsetData($key, $value, $defaultModel = null) {
        $_this = & self::getInstance();
        $listAssociation = $legend = false;
        if (is_array($value)) {

            if (!empty($value['legend'])) {
                $legend = $value['legend'];
                unset($value['legend']);
            }

            if (!empty($value['listAssociation'])) {
                $listAssociation = $value['listAssociation'];
                unset($value['listAssociation']);
            }

            if (!empty($value['lines'])) {
                $lines = $_this->_parseLinesData($value['lines'], $defaultModel);
                unset($value['lines']);
            } else {
                $lines = $_this->_parseLinesData($value, $defaultModel);
            }
        } else {
            $lines = array($_this->_parseLineData($value, $defaultModel));
        }

        return compact('legend', 'lines', 'listAssociation');
    }

    private function _parseLinesData($value, $defaultModel = null) {
        $_this = & self::getInstance();
        $linesData = array();
        foreach ($value as $line) {
            $linesData[] = $_this->_parseLineData($line, $defaultModel);
        }
        return $linesData;
    }

    private function _parseLineData($value, $defaultModel = null) {
        $_this = & self::getInstance();
        if (is_array($value)) {
            $lineData = array();
            foreach ($value as $subKey => $subValue) {
                $lineData[] = $_this->_parseFieldData($subKey, $subValue, $defaultModel);
            }
        } else {
            $lineData = array($_this->_parseFieldData(0, $value, $defaultModel));
        }

        return $lineData;
    }

    private function _parseFieldData($subKey, $subValue, $defaultModel = null) {
        $_this = & self::getInstance();
        if (!is_int($subKey)) {
            $name = $subKey;
            $options = $subValue;
        } else {
            $name = $subValue;
            $options = array();
        }

        $nameParts = explode('.', $name);

        if (count($nameParts) == 1 && $defaultModel) {
            $name = "$defaultModel.{$nameParts[0]}";
        }

        return compact('name', 'options');
    }

}

?>
