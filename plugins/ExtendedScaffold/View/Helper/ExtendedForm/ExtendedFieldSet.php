<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');

class ExtendedFieldSet {

    /**
     *
     * @var \FieldSetDefinition
     */
    private $fieldsetData;

    /**
     *
     * @var ExtendedFormHelper 
     */
    private $parent;

    public function __construct(ExtendedFormHelper $parent, \FieldSetDefinition $fieldsetData, $blacklist) {
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
        return $this->fieldsetData->getLabel() ?
                $this->parent->Html->tag('legend', $this->fieldsetData->getLabel()) :
                '';
    }

    private function _inputs() {
        return $this->parent->FieldSetLayout->fieldSet(
                        $this->_lines()
        );
    }

    private function _lines() {
        $lines = array();
        foreach ($this->fieldsetData->getLines() as $line) {
            $line = $this->_line($line);
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    private function _line(\FieldRowDefinition $line) {
        $columns = array();
        foreach ($line->getFields() as $field) {
            $column = $this->_column($field);
            if (!empty($column)) {
                list($label, $content) = $column;
                $columns[$label] = $content;
            }
        }
        return $columns;
    }

    private function _column(\FieldDefinition $field) {
        return array(
            $this->_fieldLabel($field),
            $this->_fieldInput($field)
        );
    }

    private function _fieldLabel(\FieldDefinition $field) {
        return __d('extended_scaffold', Inflector::humanize($field->getName()));
    }

    private function _fieldInput(\FieldDefinition $field) {
        $options = $field->getOptions();
        $options['div'] = false;
        $options['label'] = false;
        return $this->parent->input($field->getName(), $options);
    }

}
