<?php

App::import('Model', 'Base.CustomDataModel');
App::import('Lib', 'ConfigurationKeys.ConfigurationKeys');

class ConfigurationKey extends CustomDataModel {

    public $displayField = 'name';
    public $primaryKey = 'name';
    public $validate = array(
        'name' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                'required' => true
            ),
            'keyExists' => array(
                'rule' => array('keyExists'),
            ),
        ),
        'setted_value' => array(
            'required' => array(
                'rule' => '/.*/i',
                'required' => true,
            ),
        ),
    );

    private function _findKeyValue($name) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($name);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$SettedConfigurationKey->alias]['value'];
        } else {
            return null;
        }
    }
    
    private function _keyIsSetted($name) {        
        return ClassRegistry::init('SettedConfigurationKey')->findByName($name) ?
            true :
            false;        
    }

    protected function customData() {
        $data = array();                

        foreach (ConfigurationKeys::getKeys() as $name) {
            $row = array(
                'name' => $name,
                'description' => ConfigurationKeys::getKeyOptions($name, 'description'),
                'default_value' => ConfigurationKeys::getKeyOptions($name, 'defaultValue'),
                'setted_value' => $this->_findKeyValue($name),
                'setted' => $this->_keyIsSetted($name),
            );

            $row['current_value'] = $row['setted_value'] ? $row['setted_value'] : $row['default_value'];
            $data[][$this->alias] = $row;
        }

        return $data;
    }

    protected function customSchema() {
        return array(
            'name' => array('type' => 'string'),
            'description' => array('type' => 'string'),
            'default_value' => array('type' => 'string'),
            'setted_value' => array('type' => 'string'),
            'current_value' => array('type' => 'string'),
            'setted' => array('type' => 'boolean'),
        );
    }

    protected function customDelete($row) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($this->id);

        if ($settedConfigurationKey) {
            return $SettedConfigurationKey->delete($settedConfigurationKey[$SettedConfigurationKey->alias][$SettedConfigurationKey->primaryKey]);
        } else {
            return false;
        }
    }

    protected function customSave($isNew) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($this->data[$this->alias]['name']);

        if (!$settedConfigurationKey) {
            $settedConfigurationKey[$SettedConfigurationKey->alias]['name'] = $this->data[$this->alias]['name'];
        }

        $settedConfigurationKey[$SettedConfigurationKey->alias]['value'] = $this->data[$this->alias]['setted_value'];

        return $SettedConfigurationKey->save($settedConfigurationKey);
    }

    public function keyExists($check, $params) {
        foreach ($check as $key) {
            if (!ConfigurationKeys::hasKey($key)) {
                return false;
            }
        }

        return true;
    }

    public function nullValidation($check, $params) {
        return true;
    }

}

?>