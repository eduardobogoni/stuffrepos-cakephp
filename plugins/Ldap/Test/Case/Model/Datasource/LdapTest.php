<?php

App::uses('Model', 'Model');

class LdapSimpleUserAccountTest extends Model {

    public $useDbConfig = 'ldapTest';
    public $alias = 'LdapUser';
    public $schema = array(
        'username' => array(
            'type' => 'string',
        ),
        'password' => array(
            'type' => 'string',
        ),
    );

}

class LdapTest extends CakeTestCase {

    private $sampleData = array(
        'username' => 'myusername',
        'password' => 'secret',
    );

    /**
     *
     * @var LdapUserTest
     */
    private $LdapUser;

    public function setUp() {
        parent::setUp();
        $this->LdapUser = new LdapSimpleUserAccountTest();
        $this->LdapUser->alias = 'LdapUser';

        $this->LdapUser->delete(
            $this->LdapUser->getDataSource()->buildDnByData(
                $this->LdapUser, $this->sampleData
            )
        );
    }

    public function tearDown() {
        $this->LdapUser->delete(
            $this->LdapUser->getDataSource()->buildDnByData(
                $this->LdapUser, $this->sampleData
            )
        );

        parent::tearDown();
    }

    public function testSchema() {
        $this->assertNotEqual($this->LdapUser->schema(), null);
    }

    public function testCreate() {
        $this->LdapUser->create();

        $result = $this->LdapUser->save(
            array(
                'LdapUser' => $this->sampleData
            )
        );                
        
        $this->assertNotEqual($result, false);
    }

    public function testDelete() {
        $this->LdapUser->create();

        $result = $this->LdapUser->save(
            array(
                'LdapUser' => $this->sampleData
            )
        );

        $this->assertNotEqual($result, false);

        $this->assertEqual(
            $this->LdapUser->delete($this->LdapUser->id)
            , true);
    }

    public function testFindFirst() {
        $this->assertEqual($this->LdapUser->find('first', array(
                'conditions' => array(
                    'LdapUser.username' => $this->sampleData['username']
                )
            )), array());

        $this->LdapUser->create();

        $this->LdapUser->save(
            array(
                'LdapUser' => $this->sampleData
            )
        );

        $result = $this->LdapUser->find('first', array(
            'conditions' => array(
                'LdapUser.username' => $this->sampleData['username']
            )
            ));

        $this->assertNotEqual($result, false);
        $this->assertNotEqual($result, array());
        $this->assertEqual(empty($result[$this->LdapUser->alias]['username']), false);        
        $this->assertEqual($result[$this->LdapUser->alias]['username'], $this->sampleData['username']);
    }

    public function testFindAll() {

        $this->assertEqual($this->LdapUser->find('all', array(
                'conditions' => array(
                    'LdapUser.username' => $this->sampleData['username']
                )
            )), array());

        $this->LdapUser->create();

        $this->LdapUser->save(
            array(
                'LdapUser' => $this->sampleData
            )
        );

        $result = $this->LdapUser->find('all', array(
            'conditions' => array(
                'LdapUser.username' => $this->sampleData['username']
            )
            ));

        $this->assertNotEqual($result, false);
        $this->assertNotEqual($result, array());
        $this->assertEqual(count($result), 1);
        $this->assertEqual(empty($result[0][$this->LdapUser->alias]), false);
        $this->assertEqual($result[0][$this->LdapUser->alias]['username'], $this->sampleData['username']);
    }

    public function testFindCount() {
        $this->assertEqual($this->LdapUser->find('count', array(
                'conditions' => array(
                    'LdapUser.username' => $this->sampleData['username']
                )
            )), 0);

        $this->LdapUser->create();

        $this->LdapUser->save(
            array(
                'LdapUser' => $this->sampleData
            )
        );

        $result = $this->LdapUser->find('count', array(
            'conditions' => array(
                'LdapUser.username' => $this->sampleData['username']
            )
            ));

        $this->assertEqual($result, 1);
    }

}
