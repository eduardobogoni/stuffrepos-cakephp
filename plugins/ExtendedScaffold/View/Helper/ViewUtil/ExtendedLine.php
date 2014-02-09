<?php

App::uses('ViewUtilExtendedFieldset', 'ExtendedScaffold.View/Helper/ViewUtil');

class ExtendedLine {

    private $fields = array();

    /**
     *
     * @var ViewUtilExtendedFieldset
     */
    private $parent;

    public function __construct(ViewUtilExtendedFieldsetHelper $parent, $data) {
        $this->parent = $parent;
        foreach ($data as $fieldData) {
            $this->fields[] = new ExtendedField($this, $fieldData);
        }
    }

    public function getParent() {
        return $this->parent;
    }

    public function getFieldInputColspan() {
        // Quantidade de células da linha
        $cellCount = $this->parent->getLinesFieldCountLcd();
        // Quantidade de células para valores
        $valuesCellCount = $cellCount - $this->getFieldCount();

        return $valuesCellCount / $this->getFieldCount();
        //return 1;
    }

    public function getFieldCount() {
        return count($this->fields);
    }

    public function output($altrow) {
        $b = '';

        $class = null;
        if ($altrow) {
            $class = ' class="altrow"';
        }

        $b = "<tr $class>";

        foreach ($this->fields as $field) {
            $b .= $field->output();
        }
        $b .= '</tr>';


        return $b;
    }

}
