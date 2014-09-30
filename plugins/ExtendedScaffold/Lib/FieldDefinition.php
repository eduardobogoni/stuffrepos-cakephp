<?php

class FieldDefinition {

    /**
     * 
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->name = $name;
        $this->options = array_merge(array(
            'accessObject' => false,
            'readAccessObject' => false,
            'accessObjectType' => false,
            'valueFunction' => false,
                ), $options);
    }

    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    public function getAccessObject() {
        return $this->options['accessObject'];
    }

    public function getAccessObjectType() {
        return $this->options['accessObjectType'];
    }
    
    public function getValueFunction() {
        return $this->options['valueFunction'];
    }

    /**
     * 
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

}
