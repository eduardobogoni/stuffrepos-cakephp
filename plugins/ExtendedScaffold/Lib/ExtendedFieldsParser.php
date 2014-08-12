<?php

App::uses('FieldDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldRowDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldSetDefinition', 'ExtendedScaffold.Lib');

class ExtendedFieldsParser {
    const EXTENDED_KEY = '_extended';

    public static function &getInstance() {
        static $instance = array();

        if (!$instance) {
            $instance[0] = new ExtendedFieldsParser();
        }
        return $instance[0];
    }

    public static function isExtendedFieldsDefinition($fields) {
        return isset($fields[self::EXTENDED_KEY]);
    }

    /**
     * 
     * @param array $fields
     * @return FieldSetDefinition[]
     * @throws Exception
     */
    public static function extractExtendedFields($fields) {
        $_this = & self::getInstance();
        if ($_this->isExtendedFieldsDefinition($fields)) {
            return $fields[self::EXTENDED_KEY];
        } else {
            throw new Exception("Parâmetro \$fields não é uma definição extendida.");
        }
    }

    public static function parseFieldsets($fieldsData, $defaultModel = null) {
        $_this = & self::getInstance();

        if ($_this->isExtendedFieldsDefinition($fieldsData)) {
            $fieldsData = $_this->extractExtendedFields($fieldsData);
            $fieldsets = array();
            foreach ($fieldsData as $dataKey => $dataValue) {
                $fieldsets[] = $_this->_parseFieldsetData($dataKey, $dataValue, $defaultModel);
            }
            return $fieldsets;
        } else {
            return array(
                $_this->_parseFieldsetData(0, array(
                    'lines' => $fieldsData
                        ), $defaultModel)
            );
        }
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
        $listAssociation = $legend = $accessObject = $accessObjectType = false;
        if (is_array($value)) {
            foreach(array('legend','listAssociation', 'accessObject','accessObjectType') as $field) {
                if (!empty($value[$field])) {
                    ${$field}= $value[$field];
                    unset($value[$field]);
                }
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

        return new FieldSetDefinition($lines, compact('legend', 'listAssociation', 'accessObject', 'accessObjectType'));
    }

    private function _parseLinesData($value, $defaultModel = null) {
        $_this = & self::getInstance();
        $linesData = array();
        foreach ($value as $subKey => $subValue) {
            $linesData[] = $_this->_parseLineData($subKey, $subValue, $defaultModel);
        }
        return $linesData;
    }

    private function _parseLineData($key, $value, $defaultModel = null) {
        if (($field = $this->_parseFieldData($key, $value, $defaultModel))) {
            $fields = array($field);
        } else if (is_array($value)) {
            $fields = array();
            foreach ($value as $subKey => $subValue) {
                $field = $this->_parseFieldData($subKey, $subValue, $defaultModel);
                if ($field === false) {
                    throw new Exception("Error on parse field: " . print_r(compact('key', 'value'), true));
                }
                $fields[] = $field;
            }
        } else {
            throw new Exception("Error on parse line: " . print_r(compact('key', 'value'), true));
        }
        return new FieldRowDefinition($fields);
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $defaultModel
     * @return FieldDefinition
     */
    private function _parseFieldData($key, $value, $defaultModel = null) {
        if ((is_int($key) && preg_match('/^\d+$/', $key) && is_string($value))) {
            $name = $value;
            $options = array();
        } else if (is_string($key) && preg_match('/^[^\d]/', $key) && is_array($value)) {
            $name = $key;
            $options = $value;
        } else {
            return false;
        }
        $nameParts = explode('.', $name);
        if (count($nameParts) == 1 && $defaultModel) {
            $name = "$defaultModel.{$nameParts[0]}";
        }
        return new FieldDefinition($name, $options);
    }

}

?>
