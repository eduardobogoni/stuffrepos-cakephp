<?php

abstract class CustomDataModel extends AppModel {

    public $useTable = false;

    protected abstract function customData();

    protected abstract function customSchema();

    /**
     * @return bool
     */
    protected abstract function customSave($isNew);

    protected abstract function customDelete($row);

    public function schema() {
        return $this->customSchema();
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
            return isset($row[$conditionAlias][$conditionField]) && $row[$conditionAlias][$conditionField] == $conditionValue;
        }
    }

    public function find($type = 'first', $query = array()) {
        $data = $this->_filter($this->customData(), $query);
        switch ($type) {
            case 'count':
                return count($data);

            case 'all':
                return $data;

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
