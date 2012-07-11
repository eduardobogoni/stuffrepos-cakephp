<?php

class ConfigurationKey extends AppModel {

    public $displayField = 'name';
    public $primaryKey = 'name';
    public $useTable = false;

    private function _getKeysData() {
        $ConfigurationKeysComponent = ClassRegistry::getObject('ConfigurationKeysComponent');
        if (empty($ConfigurationKeysComponent)) {
            throw new Exception("Objeto 'ConfigurationKeysComponent' não foi encontrado em ClassRegistry (Foi adicionado como componente no controller?).");
        }

        $keys = $ConfigurationKeysComponent->getKeys();

        $data = array();

        foreach ($keys as $name => $options) {
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

    private function _findKeyValue($name) {
        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($name);
        if ($settedConfigurationKey) {
            return $settedConfigurationKey[$SettedConfigurationKey->alias]['value'];
        } else {
            return null;
        }
    }

    public function find($type = 'first', $query = array()) {
        $keysData = $this->_getKeysData();
        switch ($type) {
            case 'count':
                return count($keysData);

            case 'all':
                $keysData = $this->_filter($keysData, $query);
                return $keysData;

            case 'first':
                $keysData = $this->_filter($keysData, $query);
                if (isset($keysData[0])) {
                    return $keysData[0];
                } else {
                    return array();
                }
        }
    }

    public function _filter($rowsData, $query) {
        if (!empty($query['conditions']) && is_array($query['conditions'])) {
            foreach ($query['conditions'] as $conditionKey => $conditionValue) {
                list($conditionAlias, $conditionField) = explode('.', $conditionKey);

                $newData = array();
                foreach ($rowsData as $index => $rowData) {
                    if (isset($rowData[$conditionAlias][$conditionField]) && $rowData[$conditionAlias][$conditionField] == $conditionValue) {
                        $newData[] = $rowData;
                    }
                }
                $rowsData = $newData;
            }
        }

        return $rowsData;
    }

    public function schema() {
        return array(
            'name' => array('type' => 'string'),
            'description' => array('type' => 'string'),
            'default_value' => array('type' => 'string'),
            'setted_value' => array('type' => 'string'),
            'current_value' => array('type' => 'string'),
        );
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        if (!empty($data)) {
            $this->set($data);
        }


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

    public function delete($id = null, $cascade = true) {
        if ($id != null) {
            $this->id = id;
        }

        $SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
        $settedConfigurationKey = $SettedConfigurationKey->findByName($this->id);

        if ($settedConfigurationKey) {
            return $SettedConfigurationKey->delete($settedConfigurationKey[$SettedConfigurationKey->alias][$SettedConfigurationKey->primaryKey], $cascade);
        } else {
            return false;
        }
    }

}

?>