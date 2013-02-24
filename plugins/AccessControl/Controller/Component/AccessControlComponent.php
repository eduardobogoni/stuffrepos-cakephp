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
    
    /**
     *
     * @var CakeRequest
     */
    private static $request;
    
    public function startup(\Controller $controller) {
        parent::startup($controller);
        self::$request = $controller->request;
    }
    
    public static function clearFilters() {
        self::$filters = array();
    }

    public static function addFilter(AccessControlFilter $filter) {
        self::$filters[] = $filter;
    }

    public static function sessionUserHasAccess($object, $objectType = null) {
        return self::userHasAccess(
                AuthComponent::user()
                , $object
                , $objectType
        );
    }
    
    public static function userHasAccess($user, $object, $objectType = null) {
        foreach(self::$filters as $filter) {
            if (!$filter->userHasAccess(self::$request, $user, $object, $objectType)) {
                return false;
            }
        }
        
        return true;
    }

}