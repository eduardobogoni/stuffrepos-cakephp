<?php

App::uses('AtomicOperation', 'Operations.Lib');
App::uses('FileOperations', 'Operations.Lib');

class AtomicOperationTest extends CakeTestCase {

    private $file1;
    private $file2;

    public function setUp() {
        parent::setUp();
        $this->file1 = tempnam(sys_get_temp_dir(), 'operations_');
        $this->file2 = tempnam(sys_get_temp_dir(), 'operations_');
        unlink($this->file1);
        unlink($this->file2);
    }

    public function tearDown() {
        @unlink($this->file1);
        @unlink($this->file2);
        parent::tearDown();
    }

    public function testSuccess() {
        $this->assertEqual(file_exists($this->file1), false);
        $this->assertEqual(file_exists($this->file2), false);

        $atomic = new AtomicOperation();
        $atomic->add(FileOperations::touch($this->file1));
        $atomic->add(FileOperations::touch($this->file2));
        $atomic->add(FileOperations::unlink($this->file2));
        $atomic->run();

        $this->assertEqual(file_exists($this->file1), true);
        $this->assertEqual(file_exists($this->file2), false);
    }

    public function testFail() {
        touch($this->file2);
        $this->assertEqual(file_exists($this->file1), false);        
        $this->assertEqual(file_exists($this->file2), true);

        $atomic = new AtomicOperation();
        $atomic->add(FileOperations::touch($this->file1));        
        $atomic->add(FileOperations::unlink($this->file2));
        $atomic->add(FileOperations::touch($this->file1));
        $atomic->run();

        $this->assertEqual(file_exists($this->file1), true);
        $this->assertEqual(file_exists($this->file2), false);
    }

}
