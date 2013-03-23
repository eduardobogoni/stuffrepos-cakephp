<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Controller/Component/AccessControl');
App::uses('Controller', 'Controller');

class AccessControlComponentFilterTest implements AccessControlFilter {

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

        $accessControl->clearFilters();
        $accessControl->addFilter(new AccessControlComponentFilterTest());
    }

    public function testUserHasAccess() {
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

    public function testSessionUserHasAccess() {
        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccess('/free', 'url')
            , true);

        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccess('/no-free', 'url')
            , false);
    }

    public function testUserHasAccessByMagicMethod() {
        $this->assertEqual(
            AccessControlComponent::userHasAccessByUrl(null, '/no-free')
            , false);

        $this->assertEqual(
            AccessControlComponent::userHasAccessByUrl(null, '/free')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccessByUrl(true, '/free')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccessByUrl(true, '/no-free')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccessByOtherObjectType(true, 'object of other type')
            , true);

        $this->assertEqual(
            AccessControlComponent::userHasAccessByOtherObjectType(false, 'object of other type')
            , false);
    }

    public function testSessionUserHasAccessByMagicMethod() {
        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccessByUrl('/free')
            , true);

        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccessByUrl('/no-free')
            , false);

        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccessByOtherObjectType('object of other type')
            , false);
    }

}
