<?php

class ExtendedFieldSet {

    public function __construct($lines, $options) {
        $this->lines = $lines;
        $this->options = array_merge(array(
            'listAssociation' => false,
            'label' => false,
            'legend' => false,
            'accessObject' => false,
            'accessObjectType' => false,
                ), $options);
       
    }
    
    public function getAccessObject() {
        return $this->options['accessObject'];
    }
    
    public function getAccessObjectType() {
        return $this->options['accessObjectType'];
    }

    public function getListAssociation() {
        return $this->options['listAssociation'];
    }

    public function getLabel() {
        foreach (array('label', 'legend') as $option) {
            if ($this->options[$option]) {
                return $this->options[$option];
            }
        }
        return false;
    }
    
    public function getLines() {
        return $this->lines;
    }
    
        
    /**
     * 
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

}
