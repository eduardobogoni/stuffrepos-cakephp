<?php

App::uses('AutoCreateTableModel', 'Base.Model');

class AutoCreateTableModelModelTest extends AutoCreateTableModel {

    public $primaryKey = 'color';
    public $_schema = array(
        'color' => array('type' => 'string'),
    );

}

class AutoCreateTableModelTest extends CakeTestCase {

    /**
     * @var AutoCreateTableModelModelTest
     */
    private $Model;

    public function setUp() {
        parent::setUp();
        Configure::write('alwaysRecreateTable.AutoCreateTableModelModelTest', true);
        $this->Model = ClassRegistry::init('AutoCreateTableModelModelTest');
        $this->Model->dropTable();
    }

    public function testFind() {
        $this->Model->find('all');
    }

    public function testSave() {
        $color = array(
            $this->Model->alias => array(
                'color' => 'Magenta',
            )
        );

        $this->Model->create();
        $result = $this->Model->save($color);
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);

        $readedColor = $this->Model->read();
        $this->assertEqual($readedColor, $color);

        $color[$this->Model->alias]['color'] = 'pink';
        $result = $this->Model->save($color);
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);

        $readedColor = $this->Model->read();
        $this->assertEqual($readedColor, $color);
    }

    public function testDelete() {
        $color = array(
            $this->Model->alias => array(
                'color' => 'Magenta',
            )
        );

        $this->Model->create();
        $result = $this->Model->save($color);
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);

        $findColor = $this->Model->findByColor('Magenta');
        $this->assertEqual(empty($findColor), false);

        $this->assertEqual($this->Model->delete('Magenta'), true);

        $findColor = $this->Model->findByColor('Magenta');
        $this->assertEqual(empty($findColor), true);
    }

    public function testNoAlwaysRenewTable() {
        Configure::write('alwaysRecreateTable.AutoCreateTableModelModelTest', false);        
        $this->Model->find('all');
    }

}
