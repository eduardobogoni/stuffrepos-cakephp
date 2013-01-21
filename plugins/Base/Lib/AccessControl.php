<?php

App::import('Component', 'Auth');

class AccessControl {

    /**
     *
     * @var AccessControl_Interface 
     */
    private static $internal;

    public static function setInternal(AccessControl_Interface $internal) {
        self::$internal = $internal;
    }

    public static function sessionUserHasAccessByUrl($url) {
        return self::$internal->userHasAccessByUrl(
                AuthComponent::user()
                , $url
        );
    }

}

interface AccessControl_Interface {

    public function userHasAccessByUrl($user, $url);
}

class AccessControl_Default implements AccessControl_Interface {

    public function userHasAccessByUrl($user, $url) {
        return true;
    }
}

AccessControl::setInternal(new AccessControl_Default());
