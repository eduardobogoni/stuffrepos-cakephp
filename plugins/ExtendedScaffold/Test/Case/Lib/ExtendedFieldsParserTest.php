<?php

App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::uses('FieldDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldRowDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldSetDefinition', 'ExtendedScaffold.Lib');

class ExtendedFieldsParserTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function parserDataProvider() {
        return array(
            array(
                array(
                    'field1' => array('empty' => 'EMPTY OPTION'),
                    'field2'
                ),
                array(
                    new FieldSetDefinition(array(
                        new FieldRowDefinition(array(
                            new FieldDefinition('field1', array('empty' => 'EMPTY OPTION')),
                                )),
                        new FieldRowDefinition(array(
                            new FieldDefinition('field2', array()),
                                ))
                            ))
                ),
            ),
        );
    }

    /**
     * @dataProvider parserDataProvider
     */
    public function test($input, $expected) {
        $output = ExtendedFieldsParser::parseFieldsets($input);
        $this->assertEqual($output, $expected);
    }

}
