<?php

App::uses('CustomDataModel', 'Base.Model');

class CustomDataModelModelTest extends CustomDataModel {
       
    public $_schema = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'color' => array('type' => 'string'),
    );

    public static $initialData = array(
        'Red',
        'Green',
        'Blue',
        'Yellow',
        'White',
        'Black',
        'Orange',
    );
    public static $customData = null;

    protected function customData() {
        $data = array();
        foreach (self::$customData as $color) {
            $data[] = array(
                'color' => $color
            );
        }
        return $data;
    }

    protected function customDelete($row) {
        $key = array_search($row['color'], self::$customData);

        if ($key === false) {
            return false;
        } else {
            unset(self::$customData[$key]);
            return true;
        }
    }

    protected function customSave($oldData, $newData) {
        if (empty($oldData)) {
            self::$customData[] = $newData['color'];
            return true;
        } else {
            $key = array_search($oldData['color'], self::$customData);

            if ($key === false) {
                return false;
            } else {
                self::$customData[$key] = $newData['color'];
                return true;
            }
        }
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
        CustomDataModelModelTest::$customData = CustomDataModelModelTest::$initialData;
        $this->Model->dropTable();
    }

    public function testFind() {
        foreach ($this->Model->find('all') as $row) {
            $first = $this->Model->findById($row[$this->Model->alias]['id']);
            $this->assertEqual($first, $row);
        }
    }

    public function testRead() {
        foreach ($this->Model->find('all') as $row) {
            $readRow = $this->Model->read(null, $row[$this->Model->alias][$this->Model->primaryKey]);
            $this->assertEqual($readRow, $row);
        }
    }

    public function testCreate() {
        $newColor = array(
            $this->Model->alias => array(
                'color' => 'Magenta',
            )
        );
       
        $beforeCustomData = CustomDataModelModelTest::$customData;
        $this->Model->create();        
        $result = $this->Model->save($newColor);
        $allRows = $this->Model->find('all');
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);

        $this->assertEqual(count(CustomDataModelModelTest::$customData), count($beforeCustomData) + 1);
        $this->assertEqual(count(CustomDataModelModelTest::$customData), count($allRows));
        foreach ($allRows as $row) {
            $this->assertNotIdentical(array_search($row[$this->Model->alias]['color'], CustomDataModelModelTest::$customData), false);
        }
    }

    public function testUpdate() {
        $color = $this->Model->find('first');
        $oldColor = $color[$this->Model->alias]['color'];
        $newColor = 'Dark ' . $oldColor;
        $color[$this->Model->alias]['color'] = $newColor;

        $this->assertNotIdentical(array_search($oldColor, CustomDataModelModelTest::$customData), false);
        $this->assertIdentical(array_search($newColor, CustomDataModelModelTest::$customData), false);

        $beforeCustomData = CustomDataModelModelTest::$customData;
        $this->Model->create();
        $result = $this->Model->save($color);
        $this->assertNotEqual($result, false);
        $this->assertNotEqual($this->Model->id, false);

        $allRows = $this->Model->find('all');
        $this->assertEqual(count(CustomDataModelModelTest::$customData), count($beforeCustomData));
        $this->assertEqual(count(CustomDataModelModelTest::$customData), count($allRows));

        $find = $this->Model->findById($color[$this->Model->alias]['id']);
        $this->assertEqual($find[$this->Model->alias]['color'], $newColor);

        $this->assertIdentical(array_search($oldColor, CustomDataModelModelTest::$customData), false);
        $this->assertNotIdentical(array_search($newColor, CustomDataModelModelTest::$customData), false);
    }

    public function testDelete() {        
        $color = $this->Model->find('first');
        $this->assertNotIdentical(array_search($color[$this->Model->alias]['color'], CustomDataModelModelTest::$customData), false);

        $this->assertNotEqual($this->Model->delete($color[$this->Model->alias]['id']), false);
        
        $find = $this->Model->findById($color[$this->Model->alias]['id']);

        $this->assertEqual(empty($find), true);
        $this->assertIdentical(array_search($color[$this->Model->alias]['color'], CustomDataModelModelTest::$customData), false);
    }

}
