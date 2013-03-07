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

    /**
     *
     * @var LdapUserTest
     */
    private $LdapUser;

    public function setUp() {
        parent::setUp();
        $this->LdapUser = new LdapSimpleUserAccountTest();        
    }

    public function testSchema() {
        $this->assertNotEqual($this->LdapUser->schema(), null);
    }

    public function testCreate() {
        $result = $this->LdapUser->save(
            array(
                'LdapUser' => array(
                    'username' => 'joao.silva',
                    'password' => 'secret',
                )
            )
        );                
        
        $this->assertNotEqual($result, false);
    }

}
