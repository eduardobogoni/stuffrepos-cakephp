<?php

App::uses('Sanitize', 'Utility');

class ConfigurationKeys {

    /**
     * @var array(key:string => options:array())
     */
    private static $keys = null;

    public static function reset() {
        self::$keys = null;
    }

    private static function _getKeys() {
        if (self::$keys === null) {
            if ((self::$keys = Configure::read('configurationKeys'))) {
                foreach (self::$keys as $key => $options) {
                    if (is_int($key)) {
                        $key = $options;
                        $options = array();
                    }
                    self::$keys[$key] = $options + array(
                        'description' => null,
                        'defaultValue' => null
                    );
                }
            }
        }
        return self::$keys;
    }

    public static function getKeys() {
        return array_keys(self::_getKeys());
    }

    public static function getRequiredKeyValue($key) {
        if (($value = self::getKeyValue($key))) {
            return $value;
        } else {
            throw new Exception(sprintf(__('Configuration value not found to key=%s'), $key));
        }
    }

    public static function getKeyValue($key) {
        self::_throwExceptionIfKeyNotExists($key);

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($key);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$SettedConfigurationKey->alias]['value'];
        } else {
            return self::$keys[$key]['defaultValue'];
        }
    }

    public static function getKeyDefaultValue($key) {
        self::_throwExceptionIfKeyNotExists($key);
        return self::$keys[$key]['defaultValue'];
    }
    
    public static function getKeyValueSql($key) {
        self::_throwExceptionIfKeyNotExists($key);
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $defaultValue = Sanitize::escape(self::$keys[$key]['defaultValue'], $SettedConfigurationKey->useDbConfig);
        $key = Sanitize::escape($key, $SettedConfigurationKey->useDbConfig);
        return <<<EOT
ifnull(
        (
    select scc.value
    from {$SettedConfigurationKey->tablePrefix}{$SettedConfigurationKey->table} scc
    where scc.name = '$key'
    limit 1)
    , '$defaultValue'
)
EOT;
    }

    public static function setKeyValue($key, $value) {
        self::_throwExceptionIfKeyNotExists($key);

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($key);

        if (empty($settedConfigurationKey)) {
            $SettedConfigurationKey->create();
            $settedConfigurationKey[$SettedConfigurationKey->alias]['name'] = $key;
        }

        $settedConfigurationKey[$SettedConfigurationKey->alias]['value'] = $value;
        return $SettedConfigurationKey->save($settedConfigurationKey);
    }

    public static function clearKeyValue($key) {
        self::_throwExceptionIfKeyNotExists($key);

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($key);

        if (!empty($settedConfigurationKey)) {
            $SettedConfigurationKey->delete($settedConfigurationKey[$SettedConfigurationKey->alias][$SettedConfigurationKey->primaryKey]);
        }
    }

    public static function hasKey($key) {
        return array_key_exists($key, self::_getKeys());
    }

    public static function getKeyOptions($key, $option = null) {
        self::_throwExceptionIfKeyNotExists($key);
        $options = self::$keys[$key];
        return $option ? $options[$option] : $option;
    }

    private static function _throwExceptionIfKeyNotExists($key) {
        if (!self::hasKey($key)) {
            throw new Exception(sprintf(__('Key not setted: %s (Keys setted: %s)'), $key, implode(',', self::getKeys())));
        }
    }

}

?>
