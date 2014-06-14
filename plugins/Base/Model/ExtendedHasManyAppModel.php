<?php

App::uses('AppModel', 'Model');

abstract class ExtendedHasManyAppModel extends AppModel {

    private $_saveParent = false;
    public $virtualFieldsSchema;

    public function __construct($id = false, $table = null, $ds = null, $hasManyUtilsAssociation = array()) {
        parent::__construct($id, $table, $ds);
        $this->Behaviors->load('Base.HasManyUtils', array(
            'associations' => $hasManyUtilsAssociation
        ));
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        if ($this->_saveParent) {
            return parent::save($data, $validate, $fieldList);
        } else {
            $options = compact('validate', 'fieldList');
            $this->_saveParent = true;
            $result = $this->saveAll($data, $options);
            $this->_saveParent = false;
            return $result;
        }
    }

    public function saveAll($data = null, $options = array()) {
        $this->begin();
        $this->set($data);
        $options['atomic'] = false;
        if (!$this->beforeSaveAll($options)) {
            return false;
        }
        $result = $this->_evaluateSaveAllResult($this->_parentSaveAll(null, $options));
        if ($result) {
            $this->commit();
        } else {
            $this->rollback();
        }
        return $result;
    }
    
    protected function beforeSaveAll($options) {
        return true;
    }

    public function _parentSaveAll($data = null, $options = array()) {
        if ($this->Behaviors->enabled('HasManyUtils')) {
            $this->set($data);
            if (!$this->_triggerOptionalBehaviorCallback('beforeSaveAll', $options)) {
                return false;
            }
            $created = parent::saveAll($this->data, $options);
            $this->_triggerOptionalBehaviorCallback('afterSaveAll', $created);

            return $created;
        } else {
            return parent::saveAll($data, $options);
        }
    }

    private function _triggerOptionalBehaviorCallback($callback, $parameter) {
        $result = true;
        foreach ($this->Behaviors->enabled() as $behavior) {
            if (method_exists($this->Behaviors->{$behavior}, $callback)) {
                if (!call_user_func(array($this->Behaviors->{$behavior}, $callback), $this, $parameter)) {
                    $result = false;
                }
            }
        }
        return $result;
    }

    private function _evaluateSaveAllResult($saveAllResult) {
        if ($saveAllResult === true) {
            return true;
        } else if ($saveAllResult === false) {
            return false;
        } else if (is_array($saveAllResult)) {
            foreach ($saveAllResult as $individualResult) {
                if (!$this->_evaluateSaveAllResult($individualResult)) {
                    return false;
                }
            }
            return true;
        } else {
            throw new Exception("Valor não mapeado para \$saveAllResult = '$saveAllResult'");
        }
    }

}
