<?php

App::uses('TransactionModel', 'Operations.Model');

class TransactionModelModelTest extends TransactionModel {
    
}

class TransactionModelTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Operations.TransactionModelModelTest',
    );

    /**
     * @var TransactionModelModelTest
     */
    private $Model;

    public function setUp() {
        parent::setUp();
        $this->Model = ClassRegistry::init('TransactionModelModelTest');
    }

    public function testCreate() {
        $this->assertEqual($this->Model instanceof TransactionModel, true);
        $this->Model->create();
        $this->assertEqual($this->Model->id, false);
        $result = $this->Model->save(array(
            $this->Model->alias => array(
                'name' => 'Fulano'
            )
        ));
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);
    }

}
