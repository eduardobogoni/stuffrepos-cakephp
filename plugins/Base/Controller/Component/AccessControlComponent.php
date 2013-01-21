<?php

//App::import('Component', 'Base.BaseModel');

class AccessControlComponent extends Component {

    const NOT_LOGGED = "NOT_LOGGED";
    const ALLOWED = "ALLOWED";
    const DENIED = "DENIED";

    /**
     * @var AppControler
     */
    private $controller;
    public $components = array(
        'Auth',
    );        

    public function initialize(&$controller) {
        parent::initialize($controller);
        $this->controller = &$controller;
        ClassRegistry::getInstance()->addObject(__CLASS__, $this);
    }

    function getAccess($url) {
        $url = $this->_parseUrl($url);
        return $this->controller->getUserAccess($this->Auth->user('id'), $url);
    }

    public function hasAccess($url) {
        return $this->getAccess($url) == self::ALLOWED;
    }

    private function _parseUrl($url) {
        if (is_array($url)) {
            $url = Router::url($url);
            $url = $this->_removeStringPrefix($url, Router::url('/'));
            $url = Router::parse($url);
        }
        if (is_string($url)) {
            $url = Router::parse($url);
        }

        if (!isset($url['controller']) && empty($url['controller'])) {
            $url['controller'] = $this->controller->params['controller'];
        }

        if (!isset($url['action']) && empty($url['action'])) {
            if (isset($url['action']) && $url['action']) {
                $url['action'] = $this->controller->params['action'];
            } else {
                $url['action'] = 'index';
            }
        }
        return $url;
    }

    private function _removeStringPrefix($string, $prefix) {
        if (strpos($string, $prefix) === 0) {
            return substr($string, strlen($prefix));
        } else {
            return $string;
        }
    }

}

?>
