<?php

App::uses('ExtendedFieldsAccessControl', 'ExtendedScaffold.Lib');
App::uses('FieldDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldRowDefinition', 'ExtendedScaffold.Lib');
App::uses('FieldSetDefinition', 'ExtendedScaffold.Lib');
App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Controller/Component/AccessControl');
App::uses('Controller', 'Controller');
App::uses('Model', 'Model');

class ExtendedFieldsAccessControlTest_AccessControlFilter implements AccessControlFilter {

    public function userHasAccess(\CakeRequest $request, $user, $object, $objectType) {
        switch ($object) {
            case 'deny':
                return false;

            case 'allow':
                return true;

            default:
                throw new Exception("Access control object \"$object\" unknown");
        }
    }

}

class ExtendedFieldsAccessControlTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
        AccessControlComponent::setRequest(new CakeRequest());
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new ExtendedFieldsAccessControlTest_AccessControlFilter());
    }

    public function testPermissions() {
        $this->assertEqual(
                AccessControlComponent::userHasAccess(array(), 'deny', 'service')
                , false
        );
        $this->assertEqual(
                AccessControlComponent::userHasAccess(array(), 'allow', 'service')
                , true
        );
    }

    public function fieldAccessProvider() {
        return array(
            array(
                'allow',
                'allow',
                true,
                true,
            ),
            array(
                'allow',
                'deny',
                true,
                true,
            ),
            array(
                'allow',
                null,
                true,
                true,
            ),
            array(
                'deny',
                'allow',
                false,
                true,
            ),
            array(
                'deny',
                'deny',
                false,
                false,
            ),
            array(
                'deny',
                null,
                false,
                false,
            ),
            array(
                null,
                'allow',
                true,
                true,
            ),
            array(
                null,
                'deny',
                false,
                false,
            ),
            array(
                null,
                null,
                true,
                true,
            ),
        );
    }

    /**
     * @dataProvider fieldAccessProvider
     */
    public function testSessionUserHasFieldAccess($accessObject, $readAccessObject, $writeAccess, $readAccess) {
        $field = new FieldDefinition('fieldXYZ', compact('accessObject', 'readAccessObject'));
        $this->assertEqual($writeAccess, ExtendedFieldsAccessControl::sessionUserHasFieldAccess($field, false), 'Write access');
        $this->assertEqual($readAccess, ExtendedFieldsAccessControl::sessionUserHasFieldAccess($field, true), 'Read access');
    }

    public function testParserFields() {
        $readLines = array();
        $writeLines = array();
        $fieldSet = array('lines' => array());
        foreach ($this->fieldAccessProvider() as $i => $fieldData) {
            list($accessObject, $readAccessObject, $writeAccess, $readAccess) = $fieldData;
            $field = new FieldDefinition('field' . $i, compact('accessObject', 'readAccessObject'));
            $fieldSet['lines'][$field->getName()] = $field->getOptions();
            if ($readAccess) {
                $readLines[] = new FieldRowDefinition((array($field)));
            }
            if ($writeAccess) {
                $writeLines[] = new FieldRowDefinition((array($field)));
            }
        }
        $input = array('_extended' => array($fieldSet));

        $write = array(new FieldSetDefinition($writeLines));
        $result = ExtendedFieldsAccessControl::parseFieldsets($input, false);
        $this->assertEqual(
                $result
                , $write
        );

        $read = array(new FieldSetDefinition($readLines));
        $this->assertEqual(
                ExtendedFieldsAccessControl::parseFieldsets($input, true)
                , $read
        );
    }

}
