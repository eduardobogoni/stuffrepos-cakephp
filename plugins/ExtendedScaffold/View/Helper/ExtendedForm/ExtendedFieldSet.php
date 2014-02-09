<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');

class ExtendedFieldSet {

    private $fieldsetData;

    /**
     *
     * @var ExtendedFormHelper 
     */
    private $parent;

    public function __construct(ExtendedFormHelper $parent, $fieldsetData, $blacklist) {
        $this->parent = $parent;
        $this->fieldsetData = $fieldsetData;
        $this->blacklist = $blacklist;
    }

    public function output() {
        return $this->parent->Html->tag(
                        'fieldSet',
                        $this->_legend() .
                        $this->_inputs()
        );
    }

    private function _legend() {
        if (empty($this->fieldsetData['legend'])) {
            return '';
        } else {
            return $this->tag('legend', $this->fieldsetData['legend']);
        }
    }

    private function _inputs() {
        return $this->parent->FieldSetLayout->fieldSet(
                        $this->_lines()
        );
    }

    private function _lines() {
        $lines = array();
        foreach ($this->fieldsetData['lines'] as $line) {
            $line = $this->_line($line);
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    private function _line($line) {
        $columns = array();
        foreach ($line as $field) {
            $column = $this->_column($field);
            if (!empty($column)) {
                list($label, $content) = $column;
                $columns[$label] = $content;
            }
        }
        return $columns;
    }

    private function _column($field) {
        if ($this->_hasAccess($field)) {
            return array(
                $this->_fieldLabel($field),
                $this->_fieldInput($field)
            );
        } else {
            return false;
        }
    }

    private function _fieldLabel($field) {
        return __(Inflector::humanize($field['name']));
    }

    private function _fieldInput($field) {
        $options = $field['options'];
        $options['div'] = false;
        $options['label'] = false;
        return $this->parent->input($field['name'], $options);
    }

    private function _hasAccess($fieldData) {        
        foreach ($fieldData['options'] as $key => $value) {
            if (($accessObjectType = AccessControlComponent::parseHasAccessByMethodName('hasAccessBy', $key)) !== false) {                
                if (!$this->parent->AccessControl->{'hasAccessBy' . Inflector::camelize($accessObjectType)}($value)) {
                    return false;
                }
            }
        }
        return true;
    }

}
