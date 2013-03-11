<?php

App::uses('Model', 'Model');

class LdapPersonTest extends Model {

    public $useDbConfig = 'ldapTest';    
    public $schema = array(
        'first_name' => array(),
        'last_name' => array(),
    );
    public $validate = array(
    );

}

class LdapTest extends CakeTestCase {

    private $sampleData = array(
        'first_name' => 'My First Name',
        'last_name' => 'My Last Name',
    );
    
    private $sampleDataModified = array(
        'first_name' => 'My First Name (Modified)',
        'last_name' => 'My Last Name (Modified)',
    );

    /**
     *
     * @var LdapPersonTest
     */
    private $LdapPerson;

    public function setUp() {
        parent::setUp();
        $this->LdapPerson = new LdapPersonTest();

        $this->LdapPerson->delete(
            $this->LdapPerson->getDataSource()->buildDnByData(
                $this->LdapPerson, $this->sampleData
            )
        );
        
        $this->LdapPerson->delete(
            $this->LdapPerson->getDataSource()->buildDnByData(
                $this->LdapPerson, $this->sampleDataModified
            )
        );
    }

    public function tearDown() {
        $this->LdapPerson->delete(
            $this->LdapPerson->getDataSource()->buildDnByData(
                $this->LdapPerson, $this->sampleData
            )
        );
        
        $this->LdapPerson->delete(
            $this->LdapPerson->getDataSource()->buildDnByData(
                $this->LdapPerson, $this->sampleDataModified
            )
        );

        parent::tearDown();
    }

    public function testSchema() {
        $this->assertEqual($this->LdapPerson->schema(), array(
            $this->LdapPerson->primaryKey => array(
                'type' => 'string',
                'length' => null,
                'null' => false,
            ),
            'first_name' => array(
                'type' => 'string',
                'length' => null,
                'null' => false,
            ),
            'last_name' => array(
                'type' => 'string',
                'length' => null,
                'null' => false,
            ),
        ));
    }

    public function testCreate() {        
        $this->LdapPerson->create();

        $result = $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );                
        
        $this->assertNotEqual($result, false);
    }

    public function testDelete() {
        $this->LdapPerson->create();

        $result = $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );

        $this->assertNotEqual($result, false);

        $this->assertEqual(
            $this->LdapPerson->delete($this->LdapPerson->id)
            , true);
    }

    public function testFindFirst() {
        $this->assertEqual($this->LdapPerson->find('first', array(
                'conditions' => array(
                    $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
                )
            )), array());

        $this->LdapPerson->create();

        $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );

        $this->assertEqual($this->LdapPerson->find('first', array(
                'conditions' => array(
                    $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
                )
            )), array(
            $this->LdapPerson->alias => $this->sampleData + array($this->LdapPerson->primaryKey => $this->LdapPerson->id)
        ));
    }

    public function testFindAll() {
        $this->assertEqual($this->LdapPerson->find('all', array(
                'conditions' => array(
                    $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
                )
            )), array());

        $this->LdapPerson->create();

        $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );

        $this->assertEqual($this->LdapPerson->find('all', array(
                'conditions' => array(
                    $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
                )
            )), array(
            0 => array(
                $this->LdapPerson->alias => $this->sampleData + array($this->LdapPerson->primaryKey => $this->LdapPerson->id)
            ))
        );
    }

    public function testFindCount() {
        $this->assertEqual($this->LdapPerson->find('count', array(
                'conditions' => array(
                    $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
                )
            )), 0);

        $this->LdapPerson->create();

        $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );

        $result = $this->LdapPerson->find('count', array(
            'conditions' => array(
                $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
            )
            ));

        $this->assertEqual($result, 1);
    }
    
    public function testUpdate() {
        $this->LdapPerson->create();

        $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );
        
         $findInstance = $this->LdapPerson->find('first', array(
            'conditions' => array(
                $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
            )
            ));
         
         $this->assertEqual(empty($findInstance[$this->LdapPerson->alias]), false);                  
         
         foreach(array_keys($this->LdapPerson->schema()) as $field) {         
             if ($field != $this->LdapPerson->primaryKey) {
                 $savedInstance = $findInstance;
                 $savedInstance[$this->LdapPerson->alias][$field] = $this->sampleDataModified[$field];
                 $instance = array(
                     $this->LdapPerson->alias => array(
                         $this->LdapPerson->primaryKey => $findInstance[$this->LdapPerson->alias][$this->LdapPerson->primaryKey],
                         $field => $this->sampleDataModified[$field]
                     )
                 );                 
                 
                 $this->assertNotEqual(
                     $this->LdapPerson->save($instance)
                     , false);
                 
                 $findInstance = $this->LdapPerson->find('first', array(
                    'conditions' => array(
                        $this->LdapPerson->alias . '.first_name' => $savedInstance[$this->LdapPerson->alias]['first_name']
                    )
                    ));
                 
                 $savedInstance[$this->LdapPerson->alias][$this->LdapPerson->primaryKey] = 
                     $findInstance[$this->LdapPerson->alias][$this->LdapPerson->primaryKey];
                 $this->assertEqual($findInstance, $savedInstance);
             }
         }
    }

    public function testMagicFindMethod() {
        $this->assertEqual(
                $this->LdapPerson->findByFirstName($this->sampleData['first_name'])
                , array()
        );

        $this->LdapPerson->create();
        $this->LdapPerson->save(
                array(
                    $this->LdapPerson->alias => $this->sampleData
                )
        );

        $this->assertEqual(
                $this->LdapPerson->findByFirstName($this->sampleData['first_name']), array(
            $this->LdapPerson->alias => $this->sampleData + array($this->LdapPerson->primaryKey => $this->LdapPerson->id)
        ));
    }

    public function testUpdatePartialFields() {
        $this->LdapPerson->create();

        $this->LdapPerson->save(
            array(
                $this->LdapPerson->alias => $this->sampleData
            )
        );
        
         $findInstance = $this->LdapPerson->find('first', array(
            'conditions' => array(
                $this->LdapPerson->alias . '.first_name' => $this->sampleData['first_name']
            )
            ));
         
         $this->assertEqual(empty($findInstance[$this->LdapPerson->alias]), false);                  
         
         foreach(array_keys($this->LdapPerson->schema()) as $field) {         
             if ($field != $this->LdapPerson->primaryKey) {
                 $savedInstance = $findInstance;
                 $savedInstance[$this->LdapPerson->alias][$field] = $this->sampleDataModified[$field];        
                 
                 $this->assertNotEqual(
                     $this->LdapPerson->save(array(
                         $this->LdapPerson->alias => array($field => $this->sampleDataModified[$field])
                     ))
                     , false);
                 
                 $findInstance = $this->LdapPerson->find('first', array(
                    'conditions' => array(
                        $this->LdapPerson->alias . '.first_name' => $savedInstance[$this->LdapPerson->alias]['first_name']
                    )
                    ));
                 
                 $savedInstance[$this->LdapPerson->alias][$this->LdapPerson->primaryKey] = 
                     $findInstance[$this->LdapPerson->alias][$this->LdapPerson->primaryKey];
                 $this->assertEqual($findInstance, $savedInstance);
             }
         }
    }

}
