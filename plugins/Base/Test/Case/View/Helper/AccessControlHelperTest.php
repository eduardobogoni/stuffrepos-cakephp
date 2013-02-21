<?php

App::uses('AccessControlComponent', 'Base.Controller/Component');
App::uses('AccessControlFilter', 'Base.Lib');
App::uses('View', 'View');
App::uses('AccessControlHelper', 'Base.View/Helper');

class AccessControlFilterTest implements AccessControlFilter {

    public function userHasAccessByUrl($user, $url) {
        return $user || $url == '/free';
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
        AccessControlComponent::addFilter(new AccessControlFilterTest());
        $View = new View();
        $this->AccessControl = new AccessControlHelper($View);
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

}