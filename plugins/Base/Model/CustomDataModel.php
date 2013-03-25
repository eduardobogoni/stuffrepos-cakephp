<?php

App::uses('Model', 'Model');
App::uses('ArrayUtil', 'Base.Lib');

abstract class CustomDataModel extends Model {

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
            if (preg_match('/\s*([a-zA-Z\._]+)\s*(?:([!=<>]{1,2}|like)(.*))?/', $conditionKey, $matches)) {
                list($conditionAlias, $conditionField) = explode('.', $matches[1]);
                $operation = isset($matches[2]) ? $matches[2] : '==';
                $rightOperand = isset($matches[3]) ? trim($matches[3]) : '';
            } else {
                throw new Exception("Condition not parsed: \"$conditionKey\"");
            }

            if (!$this->_isFieldInSchema($conditionAlias, $conditionField)) {
                throw new Exception("Field \"$conditionAlias.$conditionField\" is not in {$this->name} model schema.");
            }

            if (!isset($row[$conditionAlias][$conditionField])) {
                throw new Exception(print_r(compact('conditionAlias', 'conditionField', 'row'), true));
            }

            $rowValue = $row[$conditionAlias][$conditionField];
            $conditionValue = $this->_buildConditionValue($conditionValue, $rightOperand);

            switch ($operation) {
                case '=':
                case '==':
                    return $rowValue == $conditionValue;

                case '<>':
                case '!=':
                    return $rowValue != $conditionValue;

                case 'like':
                    try {
                        return $this->_like($rowValue, $conditionValue);
                    } catch (Exception $ex) {
                        throw new Exception(print_r(compact('conditionAlias', 'conditionField', 'row', 'rowValue', 'conditionValue'), true), 0, $ex);
                    }

                default:
                    throw new Exception("Operation not mapped: " . print_r(compact('operation', 'conditionKey', 'matches'), true));
            }
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

    private function orderResults($results, $query) {
        if ($this->displayField) {
            $displayFieldSchema = $this->schema($this->displayField);
            $_THIS = $this;
            usort(
                    $results
                    , function($r1, $r2) use ($_THIS, $displayFieldSchema) {
                        $r1 = $r1[$_THIS->alias][$_THIS->displayField];
                        $r2 = $r2[$_THIS->alias][$_THIS->displayField];
                        switch ($displayFieldSchema['type']) {
                            case 'string':
                                return strcmp($r1, $r2);

                            default:
                                if ($r1 < $r2) {
                                    return -1;
                                } else if ($r1 > $r2) {
                                    return 1;
                                } else {
                                    return 0;
                                }
                        }
                    }
            );
        }
        
        return $results;
    }

    private function paginateResults($results, $query) {
        if (!empty($query['limit'])) {
            $offset = 0;
            if (!empty($query['page'])) {
                $offset = ($query['page'] - 1) * $query['limit'];
            }
            return array_slice($results, $offset, $query['limit']);
        } else {
            return $results;
        }
    }

    public function find($type = 'first', $query = array()) {
        if ($this->useCache && $this->cache !== null) {
            $data = $this->cache;
        } else {
            $this->cache = $this->customData();
            $data = $this->cache;
        }

        foreach ($this->Behaviors->enabled() as $behaviorName) {
            if (is_array($beforeFindQuery = $this->Behaviors->{$behaviorName}->beforeFind($this, $query))) {
                $query = $beforeFindQuery;
            }
        }

        $data = $this->_filter($data, $query);        
        foreach ($this->Behaviors->enabled() as $behaviorName) {
            if (is_array($afterFindResults = $this->Behaviors->{$behaviorName}->afterFind($this, $data, true))) {
                $data = $afterFindResults;
            }
        }

        $data = $this->orderResults($data, $query);
        $data = $this->paginateResults($data, $query);

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

        if ($this->customSave(empty($this->data[$this->alias][$this->primaryKey]))) {
            $this->clearCache();
            return true;
        } else {
            return false;
        }
    }

    public function delete($id = null, $cascade = true) {
        if ($id) {
            $this->id = $id;
        }
        $row = $this->find('first', array(
            'conditions' => array(
                "{$this->alias}.{$this->primaryKey}" => $this->id
            )
                ));
        return $this->customDelete($row);
    }

    public function clearCache() {
        $this->cache = null;
    }

    private function _like($rowValue, $conditionValue) {
        return preg_match($this->_likePregPattern($conditionValue), $rowValue);
    }

    private function _likePregPattern($check) {
        if (!preg_match_all('/(%+|[^%]+)/', $check, $matches)) {
            throw new Exception("Not matched: '$check'");
        }

        $pattern = '/^';
        foreach ($matches[0] as $part) {
            $pattern .= preg_match('/^%+$/', $part) ? '.*' : preg_quote($part);
        }
        $pattern .= '$/';

        return $pattern;
    }

    private function _buildConditionValue($conditionValue, $rightOperand) {
        if ($rightOperand == '') {
            return $conditionValue;
        } else {

            if (preg_match_all("/('[^']*'|\?|\|{2})/", $rightOperand, $matches)) {
                $value = '';
                $conditionValues = ArrayUtil::arraylize($conditionValue);
                $valueIndex = 0;
                foreach ($matches[0] as $part) {
                    if ($part == '?') {
                        $value .= $conditionValues[$valueIndex++];
                    } else if (preg_match("/'([^']*)'/", $part, $subMatches)) {
                        $value .= $subMatches[1];
                    } else if ($part == '||') {
                        // Do nothing
                    } else {
                        throw new Exception("No matched: $part => $rightOperand");
                    }
                }

                return $value;
            } else {
                throw new Exception("No matched: $rightOperand");
            }
        }
    }

}

?>
