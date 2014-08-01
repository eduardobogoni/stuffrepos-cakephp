<?php

class FieldRowDefinition {

    public function __construct($fields) {
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
