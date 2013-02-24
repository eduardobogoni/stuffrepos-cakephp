<?php

App::uses('Component', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Lib');

class AccessControlComponent extends Component {

    /**
     *
     * @var AccessControlFilter[] 
     */
    private static $filters = array();
    
    public static function clearFilters() {
        self::$filters = array();
    }

    public static function addFilter(AccessControlFilter $filter) {
        self::$filters[] = $filter;
    }

    public static function sessionUserHasAccessByUrl($url) {
        return self::userHasAccessByUrl(
                AuthComponent::user()
                , $url
        );
    }
    
    public static function userHasAccessByUrl($user, $url) {
        foreach(self::$filters as $filter) {
            if (!$filter->userHasAccessByUrl($user,$url)) {
                return false;
            }
        }
        
        return true;
    }

}