<?php

App::import('Model', 'StuffreposBase.CustomDataModel');
App::import('Lib', 'StuffreposConfigurationKeys.ConfigurationKeys');

class ConfigurationKey extends CustomDataModel {

    public $displayField = 'name';
    public $primaryKey = 'name';    

    private function _findKeyValue($name) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($name);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$SettedConfigurationKey->alias]['value'];
        } else {
            return null;
        }
    }

    protected function customData() {
        $data = array();                

        foreach (ConfigurationKeys::getInstance()->getKeys() as $name => $options) {
            $row = array(
                'name' => $name,
                'description' => $options['description'],
                'default_value' => $options['defaultValue'],
                'setted_value' => $this->_findKeyValue($name),
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
        );
    }

    protected function customDelete($row) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($this->id);

        if ($settedConfigurationKey) {
            return $SettedConfigurationKey->delete($settedConfigurationKey[$SettedConfigurationKey->alias][$SettedConfigurationKey->primaryKey], $cascade);
        } else {
            return false;
        }
    }

    protected function customSave($isNew) {
        if (empty($this->data[$this->alias]['name']) || !isset($this->data[$this->alias]['setted_value'])) {
            return false;
        }

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($this->data[$this->alias]['name']);

        if (!$settedConfigurationKey) {
            $settedConfigurationKey[$SettedConfigurationKey->alias]['name'] = $this->data[$this->alias]['name'];
        }

        $settedConfigurationKey[$SettedConfigurationKey->alias]['value'] = $this->data[$this->alias]['setted_value'];

        return $SettedConfigurationKey->save($settedConfigurationKey);
    }

}

?>