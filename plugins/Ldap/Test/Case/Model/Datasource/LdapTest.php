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

}
