<?php

App::uses('ConfigurationKeys', 'ConfigurationKeys.Lib');

class ConfigurationKeysTest extends CakeTestCase {

    private $oldConfigurationKeys;
    public $fixtures = array(
        'plugin.ConfigurationKeys.SettedConfigurationKey',
    );

    public function setUp() {
        parent::setUp();

        $this->oldConfigurationKeys = Configure::read('configurationKeys');

        Configure::write('configurationKeys', array(
            'without_default_value' => array(
                'description' => 'Configuration without default value.',
            ),
            'with_default_value' => array(
                'description' => 'Configuration with default value.',
                'defaultValue' => 'any value'
            ),
        ));

        ConfigurationKeys::reset();

        foreach (ConfigurationKeys::getKeys() as $key) {
            ConfigurationKeys::clearKeyValue($key);
        }
    }

    public function tearDown() {
        Configure::write('configurationKeys', $this->oldConfigurationKeys);
        ConfigurationKeys::reset();
        parent::tearDown();
    }

    public function testGetKeyValue() {
        $this->assertEqual(ConfigurationKeys::getKeyValue('without_default_value'), null);
        $this->assertEqual(ConfigurationKeys::getKeyValue('with_default_value'), 'any value');
        $this->assertEqual(ConfigurationKeys::getRequiredKeyValue('with_default_value'), 'any value');
    }

    /**
     * @expectedException Exception
     */
    public function testGetRequiredKeyValueException() {
        ConfigurationKeys::getRequiredKeyValue('without_default_value');
    }

    public function testSetKeyValue() {
        ConfigurationKeys::setKeyValue('with_default_value', 'other value');
        $this->assertEqual(ConfigurationKeys::getKeyValue('with_default_value'), 'other value');
    }

    public function testClearKeyValue() {
        $this->assertEqual(ConfigurationKeys::getKeyValue('with_default_value'), 'any value');
        ConfigurationKeys::setKeyValue('with_default_value', 'other value');
        $this->assertEqual(ConfigurationKeys::getKeyValue('with_default_value'), 'other value');
        ConfigurationKeys::clearKeyValue('with_default_value');
        $this->assertEqual(ConfigurationKeys::getKeyValue('with_default_value'), 'any value');

        $this->assertEqual(ConfigurationKeys::getKeyValue('without_default_value'), null);
        ConfigurationKeys::setKeyValue('without_default_value', 'a value');
        $this->assertEqual(ConfigurationKeys::getKeyValue('without_default_value'), 'a value');
        ConfigurationKeys::clearKeyValue('without_default_value');
        $this->assertEqual(ConfigurationKeys::getKeyValue('without_default_value'), null);
    }

    public function testGetKeys() {
        $this->assertEqual(
            ConfigurationKeys::getKeys()
            , array(
            'without_default_value',
            'with_default_value'
            )
        );
    }

    public function testHasKey() {
        foreach (ConfigurationKeys::getKeys() as $key) {
            $this->assertEqual(ConfigurationKeys::hasKey($key), true);
            $this->assertEqual(ConfigurationKeys::hasKey($key . ' not exists'), false);
        }
    }

}
