<?php

class ConfigurationKeysComponent extends Component {

    private $keys = array();

    public function initialize(&$controller) {
        parent::initialize($controller);
        //$this->controller = &$controller;
        ClassRegistry::getInstance()->addObject(__CLASS__, $this);
    }

    public function addKey($key, $options = array()) {
        $this->keys[$key] = $this->_mergeDefaultOptions($options);
    }
    
    public function getKeys() {
        return $this->keys;
    }
    
    private function _mergeDefaultOptions($options) {
        foreach(array('description','defaultValue') as $option) {
            if (!isset($options[$option])) {
                $options[$option] = null;
            }
        }
        
        return $options;
    }
}

?>