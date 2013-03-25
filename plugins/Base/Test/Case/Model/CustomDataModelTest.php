<?php

App::uses('CustomDataModel', 'Base.Model');

class CustomDataModelModelTest extends CustomDataModel {

    public static $initialData = array(
        array('CustomDataModelModelTest' => array('id' => 1, 'color' => 'Red')),
        array('CustomDataModelModelTest' => array('id' => 2, 'color' => 'Green')),
        array('CustomDataModelModelTest' => array('id' => 3, 'color' => 'Blue')),
        array('CustomDataModelModelTest' => array('id' => 4, 'color' => 'Yellow')),
        array('CustomDataModelModelTest' => array('id' => 5, 'color' => 'White')),
        array('CustomDataModelModelTest' => array('id' => 6, 'color' => 'Black')),
        array('CustomDataModelModelTest' => array('id' => 7, 'color' => 'Orange')),
    );

    protected function customData() {
        return self::$initialData;
    }

    protected function customDelete($row) {
        return false;
    }

    protected function customSave($isNew) {
        return false;
    }

    protected function customSchema() {
        return array(
            'id' => array('type' => 'integer'),
            'color' => array('type' => 'string'),
        );
    }

}

class CustomDataModelTest extends CakeTestCase {

    /**
     * @var CustomDataModelModelTest
     */
    private $Model;

    public function setUp() {
        parent::setUp();

        $this->Model = ClassRegistry::init('CustomDataModelModelTest');
        $this->Model->clearCache();
    }

    public function testFindAll() {
        $this->assertEqual(
            $this->Model->find('all')
            , CustomDataModelModelTest::$initialData
        );
    }

    public function testFindByMagicMethod() {
        foreach ($this->Model->find('all') as $row) {
            foreach ($row[$this->Model->alias] as $field => $value) {
                $findRow = $this->Model->{'findBy' . Inflector::camelize($field)}($value);
                $this->assertEqual($findRow, $row, 'testFindByMagicMethod - ' . $field);
            }
        }
    }

    public function testRead() {
        foreach ($this->Model->find('all') as $row) {
            $readRow = $this->Model->read(null, $row[$this->Model->alias][$this->Model->primaryKey]);
            $this->assertEqual($readRow, $row);
        }
    }

    public function testFindWithConditions() {
        foreach ($this->Model->find('all') as $row) {
            $findRow = $this->Model->find('first', array(
                'conditions' => array(
                    "{$this->Model->alias}.{$this->Model->primaryKey}" => $row[$this->Model->alias][$this->Model->primaryKey]
                )
                ));
            $this->assertEqual($findRow, $row);
        }
    }

    public function testFindWithLikeCondition() {
        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like" => 'B%'
                )
            )), array(
            array($this->Model->alias => array('id' => 3, 'color' => 'Blue')),
            array($this->Model->alias => array('id' => 6, 'color' => 'Black')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like" => '%ll%'
                )
            )), array(
            array($this->Model->alias => array('id' => 4, 'color' => 'Yellow')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like" => '%nge'
                )
            )), array(
            array('CustomDataModelModelTest' => array('id' => 7, 'color' => 'Orange')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like" => 'W%%e'
                )
            )), array(
            array('CustomDataModelModelTest' => array('id' => 5, 'color' => 'White')),
        ));
    }

    public function testFindWithLikeConditionAndVariable() {

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like '%' || ? || '%'" => 'll'
                )
            )), array(
            array($this->Model->alias => array('id' => 4, 'color' => 'Yellow')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like ? || '%' " => 'B'
                )
            )), array(
            array($this->Model->alias => array('id' => 3, 'color' => 'Blue')),
            array($this->Model->alias => array('id' => 6, 'color' => 'Black')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like '%' || ? " => 'nge'
                )
            )), array(
            array('CustomDataModelModelTest' => array('id' => 7, 'color' => 'Orange')),
        ));

        $this->assertEqual($this->Model->find('all', array(
                'conditions' => array(
                    "{$this->Model->alias}.color like ? || '%%' || ? " => array('W','e')
                )
            )), array(
            array('CustomDataModelModelTest' => array('id' => 5, 'color' => 'White')),
        ));
    }

}
