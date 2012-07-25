<?php

abstract class CustomDataModel extends AppModel {

    public $useTable = false;
    public $useCache = true;
    private $cache = null;

    /**
     * @return array 
     */
    protected abstract function customData();

    /**
     * @return array 
     */
    protected abstract function customSchema();

    /**
     * @param $isNew bool
     * @return bool
     */
    protected abstract function customSave($isNew);

    /**
     * @param $row array
     * @return bool 
     */
    protected abstract function customDelete($row);

    public function schema($field = false) {
        $this->_schema = $this->customSchema();
        if (is_string($field)) {
            if (isset($this->_schema[$field])) {
                return $this->_schema[$field];
            } else {
                return null;
            }
        }
        return $this->_schema;
    }

    public function _filter($rowsData, $query) {
        if (!empty($query['conditions']) && is_array($query['conditions'])) {
            foreach ($query['conditions'] as $conditionKey => $conditionValue) {
                $newData = array();
                foreach ($rowsData as $row) {
                    if ($this->_filterInCondition($row, $conditionKey, $conditionValue)) {
                        $newData[] = $row;
                    }
                }
                $rowsData = $newData;
            }
        }

        return $rowsData;
    }

    private function _filterInCondition($row, $conditionKey, $conditionValue) {
        if ($conditionKey == 'or') {
            foreach ($conditionValue as $subConditionKey => $subConditionValue) {
                if ($this->_filterInCondition($row, $subConditionKey, $subConditionValue)) {
                    return true;
                }
            }
            return false;
        } else {
            list($conditionAlias, $conditionField) = explode('.', $conditionKey);
            if (!$this->_isFieldInSchema($conditionAlias, $conditionField)) {
                throw new Exception("Field \"$conditionAlias.$conditionField\" is not in {$this->name} model schema.");
            }

            return isset($row[$conditionAlias][$conditionField]) && $row[$conditionAlias][$conditionField] == $conditionValue;
        }
    }

    private function _isFieldInSchema($alias, $field) {
        if ($alias == $this->alias) {
            $schema = $this->schema();
        } else {
            $schema = array();
        }

        return in_array($field, array_keys($schema));
    }

    public function find($type = 'first', $query = array()) {
        if ($this->useCache && $this->cache !== null) {
            $data = $this->cache;
        } else {
            $this->cache = $this->_filter($this->customData(), $query);
            $data = $this->cache;
        }

        switch ($type) {
            case 'count':
                return count($data);

            case 'all':
                return $data;

            case 'list':
                $list = array();
                foreach ($data as $row) {                    
                    $list[$row[$this->alias][$this->primaryKey]] = $row[$this->alias][$this->displayField];
                }
                asort($list);
                return $list;

            case 'first':
                if (isset($data[0])) {
                    return $data[0];
                } else {
                    return array();
                }
        }
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        if ($data) {
            $this->set($data);
        }

        if (!$this->beforeSave()) {
            return false;
        }

        if (!$this->validates()) {
            return false;
        }

        return $this->customSave(empty($this->data[$this->alias][$this->primaryKey]));
    }

    public function delete($id = null, $cascade = true) {
        if ($id) {
            $this->id;
        }
        $row = $this->find('first', array(
            'conditions' => array(
                "{$this->alias}.{$this->primaryKey}" => $this->id
            )
                ));
        return $this->customDelete($row);
    }

}

?>
