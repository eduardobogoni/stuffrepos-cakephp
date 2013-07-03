<?php

App::uses('UndoableOperation', 'Operations.Lib');

interface CommitableOperation extends UndoableOperation {
    
    /**
     * @return void
     */
    public function commit();
}