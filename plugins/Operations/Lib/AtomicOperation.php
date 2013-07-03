<?php

class AtomicOperation {

    private $operations = array();

    public function add(UndoableOperation $operation) {
        $this->operations[] = $operation;
    }

    public function run() {
        $executed = array();
        $error = null;

        foreach ($this->operations as $operation) {
            try {
                if ($operation->run()) {
                    array_unshift($executed, $operation);
                } else {
                    $error = $operation;
                    break;
                }
            } catch (Exception $ex) {
                $error = $operation;
                break;
            }
        }

        if ($error != null) {
            foreach ($executed as $operation) {
                try {
                    $operation->undo();
                } catch (Exception $ex) {
                    //Ignores fail
                }
            }
            throw new Exception("Error on execute operation \"$error\"");
        }

        foreach ($this->operations as $operation) {
            if ($operation instanceof CommitableOperation) {
                try {
                    $operation->commit();
                } catch (Exception $ex) {
                    //Ignores fail
                }
            }
        }
    }

    public function __toString() {
        $b = '';
        foreach ($this->operations as $operation) {
            $b .= "$operation\n";
        }
        return $b;
    }

}