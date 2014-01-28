<?php

App::import('Model', 'Base.CustomDataModel');
App::import('Lib', 'ConfigurationKeys.ConfigurationKeys');

class ConfigurationKey extends CustomDataModel {

    public $alwaysInitialize = true;
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
            'listOptions' => array(
                'rule' => 'listOptionsValidation',
                'message' => 'Valor informado não está contido na lista de valores permitidos',
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
            $data[] = $row;
        }

        return $data;
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

    protected function customSave($oldData, $newData) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($newData['name']);

        if (!$settedConfigurationKey) {
            $settedConfigurationKey[$SettedConfigurationKey->alias]['name'] = $newData['name'];
        }

        $settedConfigurationKey[$SettedConfigurationKey->alias]['value'] = $newData['setted_value'];

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
    
    public function listOptionsValidation($check) {
        if (empty($this->data[$this->alias]['name'])) {
            return false;
        }
        $listOptions = ConfigurationKeys::getKeyOptions($this->data[$this->alias]['name'], 'listOptions');
        if (is_array($listOptions)) {
            foreach($check as $value) {
                if (!in_array($value, $listOptions)) {
                    return false;
                }
            }
            return true;
        }
        else {
            return true;
        }
    }

}

?>