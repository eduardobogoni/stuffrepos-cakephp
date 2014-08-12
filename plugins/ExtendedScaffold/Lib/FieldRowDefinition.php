<?php

class FieldRowDefinition {

    public function __construct($fields) {
        if (!is_array($fields)) {
            throw new InvalidArgumentException('Argument "$fields" is not a array');
        }
        foreach($fields as $i => $field) {
            if (!($field instanceof FieldDefinition)) {
                throw new InvalidArgumentException("Argument \"\$fields[$i]\" is not a FieldDefinition instance");
            }
        }
        $this->fields = $fields;
    }

    /**
     * 
     * @return FieldDefinition[]
     */
    public function getFields() {
        return $this->fields;
    }

}
