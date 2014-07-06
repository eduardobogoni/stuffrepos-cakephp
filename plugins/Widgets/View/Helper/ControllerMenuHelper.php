<?php

App::uses('ArrayUtil', 'Base.Lib');
App::uses('AppHelper', 'View/Helper');
App::uses('ControllerInspector', 'Base.Lib');

/**
 * Monta menus automaticamente baseados em convenções ou configurações
 * por controller.
 */
class ControllerMenuHelper extends AppHelper {

    public $helpers = array(
        'Html',
        'AccessControl.AccessControl',
        'Base.CakeLayers',
        'Widgets.ActionList',
    );

    /**
     * @var array
     */
    private $defaultActions = array(
        array(
            'url' => array('action' => 'index'),
            'hasId' => false,
            'format' => 'Listar %s',
            'plural' => true
        ),
        array(
            'url' => array('action' => 'add'),
            'hasId' => false,
            'format' => 'Novo(a) %s',
            'plural' => false
        ),
        array('url' => array('action' => 'view'), 'hasId' => true, 'format' => 'Visualizar %s', 'plural' => false),
        array('url' => array('action' => 'edit'), 'hasId' => true, 'format' => 'Editar %s', 'plural' => false),
        array('url' => array('action' => 'delete'), 'hasId' => true, 'format' => 'Remover %s', 'plural' => false,
            'question' => 'Tem certeza de que deseja remover?', 'post' => true)
    );

    /**
     * @var array
     */
    public $settings = array(
        'listLayoutBeforeAll' => '',
        'listLayoutAfterAll' => '',
        'listLayoutBeforeEach' => '',
        'listLayoutAfterEach' => '',
        'tableLayoutBeforeAll' => '',
        'tableLayoutAfterAll' => '',
        'tableLayoutBeforeEach' => '',
        'tableLayoutAfterEach' => '',
        'layout' => ActionListHelper::LAYOUT_LIST,
        'skipActions' => array(),
        'controller' => null,
        'id' => null,
        'debug' => false,
    );

    /**
     * @var array
     */
    private $moduleMenuDefaultOptions = array(
        'shortFormat' => false,
        'skipNoRequiredIdActions' => false,
        'layout' => ActionListHelper::LAYOUT_LIST,
    );

    /**
     * @var array
     */
    private $instanceMenuDefaultOptions = array(
        'shortFormat' => true,
        'skipNoRequiredIdActions' => true,
        'layout' => ActionListHelper::LAYOUT_TABLE,
    );

    /**
     * 
     * @param array $options
     * @return string
     */
    public function moduleMenu($options = array()) {
        $options = $this->_buildOptions($this->moduleMenuDefaultOptions, $options);
        return $this->_menuByOptions(
                        $options
        );
    }

    /**
     * 
     * @param array $instance
     * @param array $options
     * @return string
     */
    public function instanceMenu($instance, $options = array()) {
        $options = $this->_buildOptions($this->instanceMenuDefaultOptions, $options);
        $options['id'] = $this->_getInstanceId($instance, $options);
        return $this->_menuByOptions($options);
    }

    /**
     * 
     * @param array $defaultOptions
     * @param array $userOptions
     * @return array
     */
    private function _buildOptions($defaultOptions, $userOptions) {
        $ret = array_merge($this->settings, $defaultOptions, $userOptions);
        $ret['_currentController'] = $this->CakeLayers->getController($this->params['controller']);
        $ret['_targetController'] = $this->CakeLayers->getController($ret['controller']);
        $ret['_targetObjectModel'] = $ret['_targetController']->{$ret['_targetController']->modelClass};
        return $ret;
    }

    /**
     * 
     * @param array $options
     * @return string
     */
    private function _menuByOptions($options) {
        $controllerActions = $this->_navigableActions(
                $this->_moduleActions($options), $options
        );
        $actions = array();
        foreach ($controllerActions as $controllerAction) {            
            $actions[] = array(
                'caption' => $this->_getTitle($controllerAction, $options),
                'post' => array_key_exists('post', $controllerAction) && $controllerAction['post'],
                'url' => $this->_buildActionUrl($controllerAction, $options),
                'question' => array_key_exists('question', $controllerAction) ? $controllerAction['question'] : false,
            );
        }
        return $this->ActionList->actionList($actions, $options);
    }

    /**
     * 
     * @param array $instance
     * @param array $options
     * @return mixed
     */
    private function _getInstanceId($instance, $options) {
        if (empty($options['model'])) {
            $model = $options['_targetController']->{
                    $options['_targetController']->modelClass
                    };
        } else {
            $model = $this->CakeLayers->getModel($options['model']);
        }
        return $instance[$model->alias][$model->primaryKey];
    }

    private function _navigableActions($actions, $options) {
        $navigableActions = array();
        foreach ($actions as $action) {
            if ($this->_isNavigable($action, $options)) {
                $navigableActions[] = $action;
            }
        }
        return $navigableActions;
    }

    private function _moduleActions($options) {
        $groups = array($this->defaultActions);
        if (!empty($options['moduleActions'])) {
            $groups[] = $options['moduleActions'];
        }
        if (!empty($options['_targetController']->moduleActions)) {
            $groups[] = $options['_targetController']->moduleActions;
        }

        $added = array();

        foreach ($groups as $group) {
            foreach ($group as $action) {
                $url = $this->_extractActionUrl($action, $options);
                $actionPath = $url['controller'] . '/' . $url['action'];
                $added[$actionPath] = $action;
            }
        }

        return $added;
    }

    public function setDefaultOption($key, $value) {
        $this->defaultOptions[$key] = $value;
    }

    private function _isNavigable($targetAction, $options) {
        $message = $this->_checkNavigable($targetAction, $options);
        if ($message == false) {
            return true;
        } else {
            if ($options['debug']) {
                debug(array('notNavigable' => compact('message', 'targetAction')));
            }

            return false;
        }
    }

    private function _checkNavigable($targetAction, $options) {
        $actionUrl = $this->_extractActionUrl($targetAction, $options);

        //Acesso negado
        if (!$this->AccessControl->hasAccessByUrl($this->_buildActionUrl($targetAction, $options))) {
            return __d('widgets','Access denied.');
        }

        $currentUrl = $this->_extractCurrentUrl($options);

        // Mesma action
        if (($currentUrl['controller'] == $actionUrl['controller']) &&
                ($currentUrl['action'] == $actionUrl['action'])) {
            return __d('widgets','Same controller/action.');
        }

        // Formato curto
        if ($options['shortFormat'] && !empty($targetAction['skipOnShort'])) {
            return __d('widgets','Skip on short format menu.');
        }

        // Não tem ID requerido
        if ($targetAction['hasId'] && $actionUrl['id'] == null) {
            return __d('widgets','Has not required id.');
        }

        // Não necessita de ID e opção para excluir
        if (!$targetAction['hasId'] && $options['skipNoRequiredIdActions']) {
            return __d('widgets','Skip no required id actions.');
        }

        // Negado pelo controller
        if ($this->_deniedAction($actionUrl, $options)) {
            return __d('widgets','Denied by controller.');
        }

        // Registrado na opção skipActions
        foreach ($options['skipActions'] as $skipActionUrl) {
            if ($this->_isUrlEquals($this->_parseUrl($skipActionUrl), $actionUrl)) {
                return __d('widgets','Action skipped.');
            }
        }

        // Método próprio no controller
        if (!$this->_isNavigableControllerMethod($targetAction, $options)) {
            return __d('widgets','Navigable method returned false.');
        }

        return false;
    }

    private function _deniedAction($actionUrl, $options) {
        if ($actionUrl['controller'] == $this->_getTargetControllerUri($options)) {
            return !ControllerInspector::actionExists($options['_targetController'], $actionUrl['action']) ||
                    (isset($options['_targetController']->notModuleActions) &&
                    array_search($actionUrl['action'], $options['_targetController']->notModuleActions) !== false);
        } else {
            return false;
        }
    }

    private function _buildActionUrl($targetAction, $options) {
        $actionUrl = $this->_extractActionUrl($targetAction, $options);

        $url = '';

        if ($actionUrl['plugin']) {
            $url .= "/{$actionUrl['plugin']}";
        }

        $url .= "/{$actionUrl['controller']}/{$actionUrl['action']}";

        if ($targetAction['hasId']) {
            if (isset($targetAction['namedParam']) && $targetAction['namedParam']) {
                $url .= "/{$targetAction['namedParam']}:{$actionUrl['id']}";
            } else {
                $url .= "/{$actionUrl['id']}";
            }
        }
        $current = Router::parse($this->request->url);
        foreach($current['named'] as $key => $value) {
            $url .= "/$key:$value";
        }
        return $url;
    }

    private function _getTitle($action, $options) {
        $actionUrl = $this->_extractActionUrl($action, $options);

        if ($options['shortFormat']) {
            return trim(sprintf($action['format'], ''));
        } else {
            $title = Inflector::singularize($actionUrl['controller']);
            $title = !empty($action['plural']) ? $actionUrl['controller'] : Inflector::singularize($actionUrl['controller']);
            $title = Inflector::humanize($title);
            $title = str_replace(' ', '', $title);
            return trim(sprintf($action['format'], __d('widgets',$title)));
        }
    }

    private function _extractCurrentUrl($options) {
        $controller = $this->params['controller'];
        $action = isset($this->params['action']) ? $this->params['action'] : 'index';
        $id = isset($this->params['pass'][0]) ? $this->params['pass'][0] : null;

        // Resolve add_or_edit
        if ($action == 'add_or_edit') {
            $action = empty($id) ? 'add' : 'edit';
        }

        return array(
            'controller' => $controller,
            'action' => $action,
            'id' => ($options['id'] ? $options['id'] : $id )
        );
    }

    private function _getTargetControllerUri($options) {
        return Inflector::underscore($options['_targetController']->name);
    }

    private function _getTargetControllerPluginUri($options) {
        return empty($options['_targetController']->plugin) ? null : Inflector::underscore($options['_targetController']->plugin);
    }

    private function _extractActionUrl($action, $options) {
        $actionUrl = $this->_parseUrl($action['url']);
        return array(
            'plugin' => $actionUrl['plugin'] ? $actionUrl['plugin'] : $this->_getTargetControllerPluginUri($options),
            'controller' => $actionUrl['controller'] ? $actionUrl['controller'] : $this->_getTargetControllerUri($options),
            'action' => isset($actionUrl['action']) ? $actionUrl['action'] : 'index',
            'id' => $this->_extractActionId($action, $options)
        );
    }

    private function _parseUrl($url) {
        if (is_string($url)) {
            $url = Router::parse($url);
        }

        $url['plugin'] = isset($url['plugin']) ? $url['plugin'] : null;
        $url['controller'] = isset($url['controller']) ? $url['controller'] : null;
        $url['action'] = isset($url['action']) ? $url['action'] : 'index';

        return $url;
    }

    private function _extractActionId($action, $options) {
        $currentUrl = $this->_extractCurrentUrl($options);

        if ($action['hasId']) {
            if (isset($action['field']) && $action['field']) {
                $obj = $this->_getTargetControllerObject($currentUrl['id']);
                $model = $this->_getTargetObjectModel();
                return $obj && $model ?
                        $this->_getObjectFieldValue($model->name, $obj, $action['field']) :
                        $this->_getCurrentActionFieldValue($action['field']);
            } else {
                return $currentUrl['id'];
            }
        } else {
            return null;
        }
    }

    private function _getTargetControllerObject($primaryKeyValue) {
        $model = $this->_getTargetObjectModel();
        return $model->find(
                        'first', array(
                    'conditions' => array(
                        "{$model->name}.{$model->primaryKey}" => $primaryKeyValue
                    )
                        )
        );
    }

    private function _getObjectFieldValue($model, $object, $field) {
        return $this->CakeLayers->modelInstanceField($model, $object, $field);
    }

    private function _getCurrentActionFieldValue($field) {
        $parts = explode('.', $field);
        $underscoreParts = array();
        foreach ($parts as $part) {
            $underscoreParts[] = Inflector::underscore($part);
        }
        $field = implode('_', $underscoreParts);

        if (!empty($this->params['named'][$field])) {
            return $this->params['named'][$field];
        }
        if (count($parts) == 1) {
            $parts = array_merge(
                    $this->CakeLayers->getModel()->alias, $parts
            );
        }
        if (ArrayUtil::hasArrayIndex($this->data, $parts)) {
            return ArrayUtil::arrayIndex($this->data, $parts);
        }

        return null;
    }

    private function _isUrlEquals($url1, $url2) {
        return $url1['controller'] == $url2['controller']
                && $url1['action'] == $url2['action'];
    }

    private function _isNavigableControllerMethod($targetAction, $options) {
        $actionUrl = $this->_extractActionUrl($targetAction, $options);
        $methodName = '__' . Inflector::variable("is_navigable_" . $actionUrl['action']);
        $controller = $this->CakeLayers->getController($actionUrl['controller']);

        if (method_exists($controller, $methodName)) {
            return call_user_func(array($controller, $methodName), array(
                        'options' => $options,
                        'currentUrl' => $this->_extractCurrentUrl($options),
                        'targetAction' => $targetAction,
                        'targetActionUrl' => $actionUrl,
                    ));
        } else {
            return true;
        }
    }

}

?>
