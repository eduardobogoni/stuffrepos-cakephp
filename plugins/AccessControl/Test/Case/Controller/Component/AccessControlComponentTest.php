<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Lib');

class AccessControlFilterTest implements AccessControlFilter {

    public function userHasAccessByUrl($user, $url) {
        return $user || $url == '/free';
    }

}

/**
 * AuthComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 * @package       Cake.Test.Case.Controller.Component
 */
class AccessControlComponentTest extends CakeTestCase {

    public function testUserHasAccessByUrl() {
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new AccessControlFilterTest());

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
    }

    public function testSesionUserHasAccessByUrl() {
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new AccessControlFilterTest());

        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccessByUrl('/free')
            , true);

        $this->assertEqual(
            AccessControlComponent::sessionUserHasAccessByUrl('/no-free')
            , false);                
    }

}
