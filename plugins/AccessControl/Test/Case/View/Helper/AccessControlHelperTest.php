<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Controller/Component/AccessControl');
App::uses('View', 'View');
App::uses('AccessControlHelper', 'AccessControl.View/Helper');
App::uses('Controller', 'Controller');

class AccessControlHelperFilterTest implements AccessControlFilter {

    public function userHasAccess(CakeRequest $request, $user, $object, $objectType) {
        return $user || ($object == '/free' && $objectType == 'url');
    }

}

class AccessControlHelperTest extends CakeTestCase {
    
    /**
     *
     * @var AccessControlHelper
     */
    private $AccessControl;

    public function setUp() {
        parent::setUp();
        $accessControl = new AccessControlComponent(
            new ComponentCollection()
            );
        $accessControl->startup(
            new Controller(
                new CakeRequest(),
                new CakeResponse()
            )
        );
        AccessControlComponent::addFilter(new AccessControlHelperFilterTest());        
        $this->AccessControl = new AccessControlHelper(new View());
    }

    public function testLink() {
        $this->assertNotEqual(
            $this->AccessControl->link('Free', '/free'),
            ''
            );
        
        $this->assertEqual(
            $this->AccessControl->link('No free', '/no-free'),
            ''
            );
    }

    public function testByAccessMagicMethod() {
        $this->assertEqual(
            $this->AccessControl->hasAccessByUrl('/free')
            , true
        );

        $this->assertEqual(
            $this->AccessControl->hasAccessByOtherObjectType('other object')
            , false
        );
    }

}