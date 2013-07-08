<?php

App::uses('CommitOperation', 'Operations.Lib');
App::uses('AnonymousFunctionOperation', 'Operations.Lib');
App::uses('Model', 'Model');

class TransactionModel extends Model {

    /**
     *
     * @var TransactionOperation
     */
    private static $operation;

    public function begin() {
        if (!self::$operation) {
            self::$operation = new TransactionOperation();
        }
    }

    public function commit() {
        if (self::$operation) {
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
        if ($data) {
            $this->set($data);
        }

        $_this = $this;
        return $this->executeOperation(new AnonymousFunctionOperation(function() use($_this, $data, $validate, $fieldList) {
                            return $_this->rawSave($data, $validate, $fieldList);
                        }));
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
        $commitInitialized = !self::$operation;
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