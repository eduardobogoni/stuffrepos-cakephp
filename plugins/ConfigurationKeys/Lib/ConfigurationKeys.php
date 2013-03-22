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

    private static $keys = null;

    protected function __construct() {
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

    public static function getKeys() {
        if (self::$keys === null) {
            if ((self::$keys = Configure::read('configurationKeys'))) {
                foreach (self::$keys as $key => $options) {
                    if (is_int($key)) {
                        $key = $options;
                        $options = array();
                    }
                    self::$keys[$key] = self::_mergeDefaultOptions($options);
                }
            }
        }
        return self::$keys;
    }

    public function getRequiredKeyValue($key) {
        if (($value = $this->getKeyValue($key))) {
            return $value;
        } else {
            throw new Exception(sprintf(__('Configuration value not found to key=%s'), $key));
        }
    }

    public function getKeyValue($key) {
        if (!self::hasKey($key)) {
            throw new Exception(sprintf(__('Key not setted: %s (Keys setted: %s)'), $key, implode(',', array_keys($this->keys))));
        }

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($key);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$SettedConfigurationKey->alias]['value'];
        } else {
            return $this->keys[$key]['defaultValue'];
        }
    }

    public static function setKeyValue($key, $value) {
        if (!self::hasKey($key)) {
            throw new Exception(sprintf(__('Key not setted: %s (Keys setted: %s)'), $key, implode(',', array_keys($this->keys))));
        }

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($key);

        if (empty($settedConfigurationKey)) {
            $SettedConfigurationKey->create();
            $settedConfigurationKey[$SettedConfigurationKey->alias]['name'] = $key;
        }

        $settedConfigurationKey[$SettedConfigurationKey->alias]['value'] = $value;
        return $SettedConfigurationKey->save($settedConfigurationKey);
    }

    public static function hasKey($key) {
        return array_key_exists($key, self::getKeys());
    }

    private static function _mergeDefaultOptions($options) {
        foreach (array('description', 'defaultValue') as $option) {
            if (!isset($options[$option])) {
                $options[$option] = null;
            }
        }

        return $options;
    }

}

?>
