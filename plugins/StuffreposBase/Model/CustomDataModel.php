<?php

abstract class CustomDataModel extends AppModel {

    public $useTable = false;

    protected abstract function customData();

    protected abstract function customSchema();

    public function schema() {
        return $this->customSchema();
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

    public function find($type = 'first', $query = array()) {
        $keysData = $this->customData();
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

}

?>
