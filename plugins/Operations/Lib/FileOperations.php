<?php

App::uses('UndoableOperation', 'Operations.Lib');
App::uses('CommitableOperation', 'Operations.Lib');

class FileOperations {

    public static function touch($fileName) {
        return new FileOperations_Touch($fileName);
    }

    public static function unlink($fileName) {
        return new FileOperations_Unlink($fileName);
    }

    public static function rename($oldFile, $newFile) {
        return new FileOperations_Rename($oldFile, $newFile);
    }
    
    public static function symLink($target, $link) {        
        return new FileOperations_SymLink($target, $link);
    }

}

class FileOperations_Touch implements UndoableOperation {

    private $fileName;
    
    /**
     *
     * @var bool
     */
    private $alreadyExists;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    public function run() {
        $this->alreadyExists = file_exists($this->fileName);
        return touch($this->fileName);
    }

    public function undo() {
        if (!$this->alreadyExists) {
            @unlink($this->fileName);
        }
    }

    public function __toString() {
        return __CLASS__ . "({$this->fileName})";
    }

}

class FileOperations_Unlink implements CommitableOperation {

    private $fileName;
    private $tempFile = null;

    public function __construct($fileName) {
        $this->fileName = $fileName;
    }

    public function run() {
        if ($this->tempFile) {
            throw new Exception("Temporary file already generated");
        }
        $this->tempFile = tempnam(dirname($this->fileName), '.FileOperations_Unlink_TempFile');
        @unlink($this->tempFile);
        return @rename($this->fileName, $this->tempFile);
    }

    public function undo() {
        if ($this->tempFile) {
            @rename($this->tempFile, $this->fileName);
        }
    }

    public function commit() {
        if ($this->tempFile) {
            @unlink($this->tempFile);
        }
    }

    public function __toString() {
        return __CLASS__ . "({$this->fileName})";
    }

}

class FileOperations_Rename implements UndoableOperation {

    private $oldFile;
    private $newFile;

    public function __construct($oldFile, $newFile) {
        $this->oldFile = $oldFile;
        $this->newFile = $newFile;
    }

    public function run() {
        return rename($this->oldFile, $this->newFile);
    }

    public function undo() {
        return rename($this->newFile, $this->oldFile);
    }

    public function __toString() {
        return __CLASS__ . "({$this->oldFile} => {$this->newFile})";
    }

}

class FileOperations_SymLink implements UndoableOperation {

    private $target;
    private $link;

    public function __construct($target, $link) {
        $this->target = $target;
        $this->link = $link;
    }

    public function run() {
        return symlink($this->target, $this->link);
    }

    public function undo() {
        @unlink($this->link);        
    }

    public function __toString() {
        return __CLASS__ . "({$this->target} => {$this->link})";
    }

}