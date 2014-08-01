<?php

App::uses('ExtendedFieldsAccessControl', 'ExtendedScaffold.Lib');
App::uses('FieldDefinition', 'ExtendedScaffold.Lib');
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

    public function testSessionUserHasFieldAccess() {
        $this->assertEqual(
                true
                , ExtendedFieldsAccessControl::sessionUserHasFieldAccess(new FieldDefinition('field1', array(
                    'accessObjectType' => null,
                    'accessObject' => null
                )))
        );
        $this->assertEqual(
                false
                , ExtendedFieldsAccessControl::sessionUserHasFieldAccess(new FieldDefinition('field2', array(
                    'accessObjectType' => null,
                    'accessObject' => 'deny'
                )))
        );
        $this->assertEqual(
                true
                , ExtendedFieldsAccessControl::sessionUserHasFieldAccess(new FieldDefinition('field3', array(
                    'accessObjectType' => null,
                    'accessObject' => 'allow'
                )))
        );
    }

    public function testParserFields() {
        AccessControlComponent::setRequest(new CakeRequest());
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new ExtendedFieldsAccessControlTest_AccessControlFilter());

        $this->assertEqual(
                ExtendedFieldsAccessControl::parseFieldsets(array(
                    '_extended' => array(
                        array(
                            'lines' => array(
                                'field1',
                                'field2' => array(
                                    'accessObjectType' => null,
                                    'accessObject' => 'deny'
                                ),
                                'field3' => array(
                                    'accessObjectType' => null,
                                    'accessObject' => 'allow'
                                ),
                            )
                        )
                    )
                ))
                , array(
            new FieldSetDefinition(array(
                new FieldRowDefinition(array(
                    new FieldDefinition('field1', array(
                        'accessObjectType' => null,
                        'accessObject' => null
                            )),
                        )),
                new FieldRowDefinition(array(
                    new FieldDefinition('field3', array(
                        'accessObjectType' => null,
                        'accessObject' => 'allow'
                            )),
                        )),
                    ), array())
                )
        );
    }

}
