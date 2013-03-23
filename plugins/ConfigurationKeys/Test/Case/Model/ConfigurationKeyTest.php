<?php

class ConfigurationKeyTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.ConfigurationKeys.SettedConfigurationKey',
    );

    /**
     *
     * @var ConfigurationKey
     */
    private $ConfigurationKey;

    public function setUp() {
        parent::setUp();
        App::import('Model', 'ConfigurationKeys.ConfigurationKey');
        $this->ConfigurationKey = ClassRegistry::init('ConfigurationKey');
        $this->assertEqual(get_class($this->ConfigurationKey), 'ConfigurationKey');

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

    public function testSaveKeyNotExists() {
        $result = $this->ConfigurationKey->save(array(
            $this->ConfigurationKey->alias => array(
                'name' => 'not a existent key',
                'setted_value' => 'value1'
            )
            )
        );
        $this->assertEqual(empty($this->ConfigurationKey->validationErrors['name']), false);
        $this->assertEqual($result, false);
    }

    public function testSaveWithoutSettedValue() {
        $result = $this->ConfigurationKey->save(array(
            $this->ConfigurationKey->alias => array(
                'name' => 'without_default_value',
            )
            )
        );
        $this->assertEqual(empty($this->ConfigurationKey->validationErrors['setted_value']), false);
        $this->assertEqual($result, false);
    }

    public function testSave() {
        $result = $this->ConfigurationKey->save(array(
            $this->ConfigurationKey->alias => array(
                'name' => 'without_default_value',
                'setted_value' => 'value1'
            )
            )
        );

        $this->assertEqual($this->ConfigurationKey->validationErrors, array());
        $this->assertNotEqual($result, false);
        $this->assertEquals(
            ConfigurationKeys::getKeyValue('without_default_value')
            , 'value1'
        );
    }

    public function testDelete() {
        $result = $this->ConfigurationKey->save(array(
            $this->ConfigurationKey->alias => array(
                'name' => 'without_default_value',
                'setted_value' => 'value1'
            )
            )
        );

        $this->assertEqual($this->ConfigurationKey->validationErrors, array());
        $this->assertNotEqual($result, false);
        $this->assertEquals(
            ConfigurationKeys::getKeyValue('without_default_value')
            , 'value1'
        );

        $configurationKey = $this->ConfigurationKey->read();

        $this->assertNotEqual(
            $this->ConfigurationKey->delete(
                $configurationKey[$this->ConfigurationKey->alias][$this->ConfigurationKey->primaryKey]
            )
            , false
        );
    }

}
