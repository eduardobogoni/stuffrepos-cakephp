<?php

App::uses('CommitableOperation', 'Operations.Lib');

class AnonymousFunctionOperation implements CommitableOperation {

    public function __construct($runFunction, $undoFunction = null, $commitFunction = null) {
        $this->runFunction = $runFunction;
        $this->undoFunction = $undoFunction;
        $this->commitFunction = $commitFunction;
    }

    public function __toString() {
        return __CLASS__ . "(?)";
    }

    public function commit() {
        if ($this->commitFunction) {
            return call_user_func($this->commitFunction);
        }
    }

    public function run() {
        return call_user_func($this->runFunction);
    }

    public function undo() {
        if ($this->undoFunction) {
            return call_user_func($this->undoFunction);
        }
    }

}
