<?php

App::uses('Component', 'Controller');

class ContextComponent extends Component {

    private $currentId;

    public function __construct(\ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings);
    }

    public function initialize(\Controller $controller) {
        parent::initialize($controller);

        $this->currentId = $controller->request->params['pass'][0];
        $this->_checkChangeContext($controller);
    }
    
    private function _checkChangeContext(\Controller $controller) {
        if (!empty($controller->request->params['named']['changeContextId'])) {
            $this->_changeContext(
                    $controller
                    , $controller->request->params['named']['changeContextId']);
        }
    }

    private function _changeContext(\Controller $controller, $contextId) {
        $controller->redirect($this->_changeUrl($controller, $contextId));
    }

    private function _changeUrl(\Controller $controller, $contextId) {
        $url = Router::parse($controller->request->url);
        unset($url['named']['changeContextId']);
        $url['pass'][0] = $contextId;
        $url = array_merge($url['named'], $url['pass'], $url);
        unset($url['named']);
        unset($url['pass']);
        return $url;
    }

    public function beforeRender(\Controller $controller) {
        $controller->set(
                'contextObjectsList'
                , $controller->{$controller->uses[0]}->find('list')
        );
        $controller->set(
                'contextCurrentId'
                , $this->getCurrentId()
        );
    }

    public function getCurrentId() {
        return $this->currentId;
    }

}
