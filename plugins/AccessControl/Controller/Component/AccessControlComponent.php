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
    
    public $components = array(
        'Session'
    );

    public function __construct(\ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings);
        $this->settings = $settings + array('filters' => array());
    }

    /**
     *
     * @var CakeRequest
     */
    private static $request;
    
    public function startup(\Controller $controller) {
        parent::startup($controller);
        $this->loadFilters();
        self::$request = $controller->request;

        if (!self::sessionUserHasAccessByUrl(self::$request->params)) {            
            $this->Session->setFlash('Acesso Negado');
            $controller->redirect('/');
            return false;
        }
    }
    
    private function loadFilters() {
        $this->clearFilters();
        foreach ($this->settings['filters'] as $filterName) {
            list($plugin, $name) = pluginSplit($filterName);
            $class = $name . 'AccessControlFilter';
            $location = ($plugin ? $plugin . '.' : '') . 'Controller/Component/AccessControl';
            App::uses($class, $location);
            $this->addFilter(new $class());
        }
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

    public static function __callStatic($method, $arguments) {
        if (preg_match('/^sessionUserHasAccessBy(.+)$/', $method, $matches)) {
            if (count($arguments) < 1) {
                trigger_error(__('Missing argument 1 for %1$s::%2$s', __CLASS__, $method), E_USER_ERROR);
            }

            return self::sessionUserHasAccess(
                    $arguments[0], Inflector::variable($matches[1])
            );
        } else if (preg_match('/^userHasAccessBy(.+)$/', $method, $matches)) {
            for ($i = 1; $i <= 2; $i++) {
                if (count($arguments) < $i) {
                    trigger_error(__('Missing argument %1$i for %2$s::%3$s', $i, __CLASS__, $method), E_USER_ERROR);
                }
            }                        

            return self::userHasAccess(
                    $arguments[0], $arguments[1], Inflector::variable($matches[1])
            );
        }

        trigger_error(__d('cake_dev', 'Method %1$s::%2$s does not exist', __CLASS__, $method), E_USER_ERROR);
    }

    public function __call($method, $arguments) {
        return self::__callStatic($method, $arguments);
    }

    public static function parseHasAccessByMethodName($prefix, $method) {
        if (preg_match("/^$prefix(.+)$/", $method, $matches)) {
            return Inflector::variable($matches[1]);
        }
        else {
            return false;
        }
    }

}