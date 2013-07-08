<?php

App::uses('UndoableOperation', 'Operations.Lib');
App::uses('CommitableOperation', 'Operations.Lib');

class ModelOperations {

    public static function save($model, $data) {
        return new ModelOperations_Save($model, $data);
    }

    public static function delete($model, $id) {
        return new ModelOperations_Delete($model, $id);
    }

}

class ModelOperations_Save implements CommitableOperation {

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

    public function __construct($model, $data) {
        if (is_string($model)) {
            $this->model = ClassRegistry::init($model);
        } else {
            $this->model = $model;
        }

        $this->data = $data;
    }

    public function __toString() {
        return __CLASS__ . "({$this->model->name}/{$this->model->alias} => " . print_r($this->data, true) . ")";
    }

    public function commit() {
        $this->model->commit();
    }

    public function run() {
        $this->model->begin();
        if (empty($this->data[$this->model->alias][$this->model->primaryKey])) {
            $this->model->create();
        }
        return $this->model->save($this->data);
    }

    public function undo() {
        $this->model->rollback();
    }

}

class ModelOperations_Delete implements CommitableOperation {

    /**
     *
     * @var Model
     */
    private $model;

    /**
     *
     * @var mixed
     */
    private $id;

    public function __construct($model, $id) {
        if (is_string($model)) {
            $this->model = ClassRegistry::init($model);
        } else {
            $this->model = $model;
        }

        $this->id = $id;
    }

    public function __toString() {
        return __CLASS__ . "({$this->model->name}/{$this->model->alias} => {$this->id})";
    }

    public function commit() {
        $this->model->commit();
    }

    public function run() {
        $this->model->begin();        
        return $this->model->delete($this->id);
    }

    public function undo() {
        $this->model->rollback();
    }

}