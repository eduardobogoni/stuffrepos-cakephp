<?php

App::uses('CommitOperation', 'Operations.Lib');
App::uses('AnonymousFunctionOperation', 'Operations.Lib');
App::uses('Model', 'Model');
App::uses('TransactionOperation', 'Operations.Lib');
App::uses('UndoableOperation', 'Operations.Lib');

class TransactionModel extends Model {

    /**
     *
     * @var TransactionOperation
     */
    private static $operation;

    public function begin() {
        if (!self::$operation) {
            self::$operation = new TransactionOperation();
            parent::begin();
        }
    }

    public function commit() {
        if (self::$operation) {
            parent::commit();
            self::$operation->commit();
            self::$operation = null;
        } else {
            throw new Exception("No commit operation initialized");
        }
    }

    public function rawSave($data = null, $validate = true, $fieldList = array()) {
        return parent::save($data, $validate, $fieldList);
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        return $this->executeOperation(new _TransactionModel_RawSaveOperation(
                        $this, $data, $validate, $fieldList));
    }

    public function rawDelete($id = null, $cascade = true) {
        return parent::delete($id, $cascade);
    }

    public function delete($id = null, $cascade = true) {
        $_this = $this;
        return $this->executeOperation(new AnonymousFunctionOperation(function() use($_this, $id, $cascade) {
                            return $_this->rawDelete($id, $cascade);
                        }));
    }

    public function executeOperation(UndoableOperation $operation) {
        $commitInitialized = self::$operation != null;
        if (!$commitInitialized) {
            $this->begin();
        }

        try {
            if (self::$operation) {
                $result = self::$operation->execute($operation);
            } else {
                $result = $operation->run();
            }
        } catch (Exception $ex) {
            $result = $ex;
        }

        if ($result == false || $result instanceof Exception) {
            if (self::$operation) {
                self::$operation->rollback();
            }

            if ($result instanceof Exception) {
                throw $result;
            } else {
                return false;
            }
        } else {
            if (!$commitInitialized) {
                $this->commit();
            }
            return true;
        }
    }

}

class _TransactionModel_RawSaveOperation implements UndoableOperation {

    /**
     *
     * @var Model
     */
    private $model;

    /**
     *
     * @var array
     */
    private $data;

    public function __construct(Model $model, $data, $validate, $fieldList) {
        $this->model = $model;
        $this->data = $data;
        $this->validate = $validate;
        $this->fieldList = $fieldList;
    }

    public function __toString() {
        return __CLASS__ . "({$this->model->name}/{$this->model->alias} => " . print_r($this->data, true) . ")".print_r($this->model->validationErrors, true);
    }

    public function run() {
        return $this->model->rawSave($this->data, $this->validate, $this->fieldList);
    }

    public function undo() {
        //Do nothing
    }

}