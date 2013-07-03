<?php

interface UndoableOperation {

    /**
     * @return boolean
     */
    public function run();

    /**
     * @return void
     */
    public function undo();

    public function __toString();
}
