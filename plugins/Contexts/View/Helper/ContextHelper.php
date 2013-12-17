<?php

App::uses('AppHelper', 'View/Helper');

class ContextHelper extends AppHelper {

    public $helpers = array(
        'ExtendedScaffold.ExtendedForm',
        'MyActionList' => array(
            'beforeAll' => '<div class="ContextMenu"><ul>',
            'afterAll' => '</ul></div>',
            'beforeEach' => '<li>',
            'afterEach' => '</li>',
        ),
    );

    public function menu($modules) {
        if (array_key_exists($this->params['controller'], $modules)) {
            return $this->MyActionList->actionList($this->_actions($modules));
        } else {
            return '';
        }
    }

    public function selector() {
        if (array_key_exists('contextObjectsList', $this->_View->viewVars)) {
            if (count($this->_View->viewVars['contextObjectsList']) > 1) {
                $id = $this->ExtendedForm->createNewDomId();
                return $this->_selectorInput($id)
                        . $this->_selectorOnChangeEvent($id);
            }
        }

        return '';
    }

    private function _selectorInput($inputId) {
        return $this->ExtendedForm->select(
                        'my_store'
                        , $this->_View->viewVars['contextObjectsList']
                        , array(
                    'class' => 'ContextSelector'
                    , 'empty' => false
                    , 'id' => $inputId
                    , 'value' => $this->_View->viewVars['contextCurrentId']
                        )
        );
    }

    private function _selectorOnChangeEvent($inputId) {
        $b = <<<EOT
<script type='text/javascript' >
                
$('#$inputId').change(function(){
    var urls = {};

EOT;
        foreach (array_keys($this->_View->viewVars['contextObjectsList']) as $contextId) {
            $b .= "urls[$contextId] = '{$this->_selectorContextUrl($contextId)}';\n";
        }
        $b .= <<<EOT
    window.location = urls[$(this).val()];

});
                       
</script>
EOT;
        return $b;
    }

    private function _selectorContextUrl($contextId) {
        $url = Router::parse($this->request->url);
        $url['named']['changeContextId'] = $contextId;
        $url = array_merge($url['named'], $url['pass'], $url);
        unset($url['named']);
        unset($url['pass']);
        return Router::url($url);
    }

    private function _actions($modules) {
        $actions = array();
        foreach ($modules as $controller => $action) {
            $linkOptions = array();
            if ($controller == $this->params['controller']) {
                $linkOptions['class'] = 'current';
            }
            $action['linkOptions'] = $linkOptions;
            $actions[] = $action;
        }
        return $actions;
    }

}