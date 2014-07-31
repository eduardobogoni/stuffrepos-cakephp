<?php

class ExtendedFieldsLine {

    public function __construct($fields) {
        $this->fields = $fields;
    }

    /**
     * 
     * @return ExtendedField[]
     */
    public function getFields() {
        return $this->fields;
    }

}
