<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Lib');
App::uses('Controller', 'Controller');

class AccessControlFilterTest implements AccessControlFilter {

    public function userHasAccess(CakeRequest $request, $user, $object, $objectType) {
        return $user || ($object == '/free' && $objectType == 'url');
    }

}

/**
 * AuthComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 * @package       Cake.Test.Case.Controller.Component
 */
class AccessControlComponentTest extends CakeTestCase {

    public $AccessControl = null;

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
    }

    public function testUserHasAccess() {
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new AccessControlFilterTest());

        $this->assertEqual(
            AccessControlComponent::userHasAccess(null, '/no-free', 'url')
            , false);

        $this->assertEqual(
            AccessControlComponent::userHasAccess(null, '/free', 'url')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccess(true, '/free', 'url')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccess(true, '/no-free', 'url')
            , true);
    }

    public function testSesionuserHasAccess() {
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new AccessControlFilterTest());

        $this->assertEqual(
            AccessControlComponent::sessionuserHasAccess('/free', 'url')
            , true);

        $this->assertEqual(
            AccessControlComponent::sessionuserHasAccess('/no-free', 'url')
            , false);
    }

}
