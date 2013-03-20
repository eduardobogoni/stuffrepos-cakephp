<?php

class SettedConfigurationKeyTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.ConfigurationKeys.SettedConfigurationKey',
    );

    /**
     *
     * @var SettedConfigurationKey
     */
    private $SettedConfigurationKey;

    public function setUp() {
        parent::setUp();
        App::import('Model', 'ConfigurationKeys.SettedConfigurationKey');
        $this->SettedConfigurationKey = ClassRegistry::init('SettedConfigurationKey');
    }

    public function testInsert() {
        $this->SettedConfigurationKey->create();
        $result = $this->SettedConfigurationKey->save(array(
            $this->SettedConfigurationKey->alias => array(
                'name' => 'name1',
                'value' => 'value1'
            )
            )
        );
        $this->assertNotEqual($result, false);

        $this->SettedConfigurationKey->create();
        $result = $this->SettedConfigurationKey->save(array(
            $this->SettedConfigurationKey->alias => array(
                'name' => 'name1',
                'value' => 'value1'
            )
            )
        );

        $this->assertEqual($result, false);
        
        $this->SettedConfigurationKey->create();
        $result = $this->SettedConfigurationKey->save(array(
            $this->SettedConfigurationKey->alias => array(
                'name' => 'name2',
                'value' => 'value1'
            )
            )
        );

        $this->assertNotEqual($result, false);
    }

}
