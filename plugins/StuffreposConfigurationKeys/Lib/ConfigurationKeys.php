<?php

class ConfigurationKeys {

    /**
     * @var ConfigurationKeys
     */
    private static $instance;

    /**
     * @return ConfigurationKeys
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new ConfigurationKeys();
        }
        return self::$instance;
    }

    private $keys = array();
    private $SettedConfigurationKey = null;

    protected function __construct() {
        $this->SettedConfigurationKey = ClassRegistry::init('StuffreposConfigurationKeys.SettedConfigurationKey');
        if (($keys = Configure::read('configurationKeys'))) {
            foreach ($keys as $key => $options) {
                if (is_int($key)) {
                    $key = $options;
                    $options = array();
                }
                $this->keys[$key] = $this->_mergeDefaultOptions($options);
            }
        }
    }

    public function addKey($key, $options = array()) {
        $this->keys[$key] = $this->_mergeDefaultOptions($options);
    }

    public function getKeys() {
        return $this->keys;
    }

    public function getRequiredKeyValue($key) {
        if (($value = $this->getKeyValue($key))) {
            return $value;
        } else {
            throw new Exception(sprintf(__('Configuration value not found to key=%s'), $key));
        }
    }

    public function getKeyValue($key) {
        if (!in_array($key, array_keys($this->keys))) {
            throw new Exception(sprintf(__('Key not setted: %s (Keys setted: %s)'), $key, implode(',', array_keys($this->keys))));
        }

        $settedConfigurationKey = $this->SettedConfigurationKey->findByName($key);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$this->SettedConfigurationKey->alias]['value'];
        } else {
            return $this->keys[$key]['defaultValue'];
        }
    }

    private function _mergeDefaultOptions($options) {
        foreach (array('description', 'defaultValue') as $option) {
            if (!isset($options[$option])) {
                $options[$option] = null;
            }
        }

        return $options;
    }

}

?>
