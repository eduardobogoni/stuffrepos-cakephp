<?php

class TransactionOperation {

    private $operations = array();

    public function execute(UndoableOperation $operation) {
        if ($operation->run()) {
            $this->operations[] = $operation;
        } else {
            $operation->undo();
            $this->rollback();
            throw new Exception("Error on executing \"$operation\"");
        }
    }

    /**
     * @return void
     */
    public function rollback() {
        while (!empty($this->operations)) {
            $operation = array_pop($this->operations);
            try {
                $operation->undo();
            } catch (Exception $ex) {
                //Ignores fail
            }
        }
    }

    /**
     * @return void
     */
    public function commit() {
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