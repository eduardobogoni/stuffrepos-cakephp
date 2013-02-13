<?php

class JournalizedBehaviorTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Journaling.JournalizedObject',
        'plugin.Journaling.Journal',
        'plugin.Journaling.JournalDetail',
    );

    public function setUp() {
        parent::setUp();
        $this->JournalizedObject = ClassRegistry::init('JournalizedObject');
        $this->JournalizedObject->Behaviors->attach('Journaling.Journalized');
        $this->JournalizedObject->Journal->name;
    }

    public function testEmptyFields() {
        $this->assertEqual($this->JournalizedObject->emptyFields(), array(
            'JournalizedObject' => array(
                'string_field' => null,
                'int_field' => null,
            )
        ));
    }

    public function testDiffValues() {
        $this->assertEqual(
            $this->JournalizedObject->diffValues(
                array(
                'JournalizedObject' => array(
                    'string_field' => 'test one',
                    'int_field' => null
                )
                )
                , array(
                'JournalizedObject' => array(
                    'string_field' => 'test two',
                    'int_field' => 0
                )
                )
            )
            , array(
            'string_field' => array(                
                'old' => 'test one',
                'current' => 'test two',
            ),
            'int_field' => array(                
                'old' => null,
                'current' => 0,
            ),
            )
        );
    }

    public function testSave() {
        $this->JournalizedObject->create();

        $this->JournalizedObject->save(array(
            'string_field' => 'test one'
        ));
        $id = $this->JournalizedObject->id;

        $expectedJournals = array(
            array(
                'Journal' => array(
                    'type' => 'create',
                    'journalized_id' => $id,
                    'journalized_type' => $this->JournalizedObject->name,
                ),
                'JournalDetail' => array(
                    array(
                        'property' => 'string_field',
                        'old_value' => null,
                        'value' => 'test one',
                    ),
                )
            ),
            array(
                'Journal' => array(
                    'type' => 'update',
                    'journalized_id' => $id,
                    'journalized_type' => $this->JournalizedObject->name,
                ),
                'JournalDetail' => array(
                    array(
                        'property' => 'int_field',
                        'old_value' => null,
                        'value' => '0',
                    )
                )
            ),
            array(
                'Journal' => array(
                    'type' => 'update',
                    'journalized_id' => $id,
                    'journalized_type' => $this->JournalizedObject->name,
                ),
                'JournalDetail' => array(
                    array(
                        'property' => 'string_field',
                        'old_value' => 'test one',
                        'value' => 'test two',
                    ),
                    array(
                        'property' => 'int_field',
                        'old_value' => '0',
                        'value' => '1234',
                    )
                )
            ),
            array(
                'Journal' => array(
                    'type' => 'delete',
                    'journalized_id' => $id,
                    'journalized_type' => $this->JournalizedObject->name,
                ),
                'JournalDetail' => array(
                    array(
                        'property' => 'string_field',
                        'old_value' => 'test two',
                        'value' => null,
                    ),
                    array(
                        'property' => 'int_field',
                        'old_value' => '1234',
                        'value' => null,
                    )
                )
            )
        );


        $this->_testSaveTestChanges($expectedJournals, 1, $id);

        $this->JournalizedObject->save(array(
            'int_field' => 0
        ));
        $this->_testSaveTestChanges($expectedJournals, 2, $id);

        $this->JournalizedObject->save(array(
            'string_field' => 'test two',
            'int_field' => 1234,
        ));
        $this->_testSaveTestChanges($expectedJournals, 3, $id);

        $this->JournalizedObject->save(array(
            'string_field' => 'test two',
        ));

        $this->_testSaveTestChanges($expectedJournals, 3, $id);

        $this->JournalizedObject->delete();
        $this->_testSaveTestChanges($expectedJournals, 4, $id);
    }

    private function _testSaveTestChanges($expectedJournals, $journalCount, $id) {

        $journals = $this->JournalizedObject->Journal->find(
            'all', array(
            'conditions' => array(
                'Journal.journalized_id' => $id
            )
            )
        );

        foreach (array_keys($journals) as $j) {
            unset($journals[$j]['Journal']['id']);
            unset($journals[$j]['Journal']['created']);

            foreach (array_keys($journals[$j]['JournalDetail']) as $jd) {
                unset($journals[$j]['JournalDetail'][$jd]['id']);
                unset($journals[$j]['JournalDetail'][$jd]['journal_id']);
            }
        }

        $expectedJournalsCounted = array();

        for ($k = 0; $k < $journalCount; $k++) {
            $expectedJournalsCounted[] = $expectedJournals[$k];
        }

        $this->assertEqual($journals, $expectedJournalsCounted);
    }

}
