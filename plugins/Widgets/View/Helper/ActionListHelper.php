<?php

App::uses('ArrayUtil', 'Base.Lib');
App::uses('AppHelper', 'View/Helper');

/**
 * Monta menus automaticamente baseados em convenções ou configurações
 * por controller;
 */
class ActionListHelper extends AppHelper {

    const LAYOUT_LIST = 'line';
    const LAYOUT_TABLE = 'table';

    public $debug = false;
    public $helpers = array(
        'Html',
        'AccessControl.AccessControl',
        'Base.CakeLayers',
    );

    /**
     * @var AppControler
     */
    private $currentController;

    /**
     * @var AppControler
     */
    private $targetController;

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
            'question' => 'Tem certeza de que deseja remover?', 'linkOptions' => array('method' => 'post'))
    );

    /**
     * @var array
     */
    private $defaultOptions = array(
        'beforeAll' => '',
        'afterAll' => '',
        'beforeEach' => '',
        'afterEach' => '',
        'tableLayoutBeforeAll' => '',
        'tableLayoutAfterAll' => '',
        'tableLayoutBeforeEach' => '',
        'tableLayoutAfterEach' => '',
        'id' => null,
        'controller' => null,
        'skipNoRequiredIdActions' => false,
        'shortFormat' => false,
        'skipActions' => array(),
        'layout' => self::LAYOUT_LIST
    );

    /**
     * @var array
     */
    private $settings;

    public function __construct(\View $View, $settings = array()) {
        parent::__construct($View, $settings);
        $this->settings = $settings;
    }

    public function outputModuleMenu($options = array()) {
        $this->options = $this->_mergeOptions($options);
        $this->currentController = $this->_foundCurrentController();
        $this->targetController = $this->_foundTargetController();
        return $this->_outputActions(
                        $this->_navigableActions(
                                $this->_controllerActions(
                                        $this->targetController
                                )
                        )
        );
    }

    private function _navigableActions($actions) {
        $navigableActions = array();
        foreach ($actions as $action) {
            if ($this->_isNavigable($action)) {
                $navigableActions[] = $action;
            }
        }
        return $navigableActions;
    }

    public function outputObjectMenu($object, $controller = null, $options = array()) {
        if ($controller) {
            $options['controller'] = $controller;
        }
        $this->options = $this->_mergeOptions($options);
        $this->currentController = $this->_foundCurrentController();
        $this->targetController = $this->_foundTargetController();
        $model = &$this->targetController->{
                $this->targetController->modelClass
                };
        $options['id'] = $object[$model->alias][$model->primaryKey];
        $options['shortFormat'] = true;
        $options['skipNoRequiredIdActions'] = true;
        $options['layout'] = self::LAYOUT_TABLE;
        return $this->outputModuleMenu($options);
    }

    private function _controllerActions(&$controller) {
        $groups = array($this->defaultActions);
        if (!empty($controller->moduleActions)) {
            $groups[] = $controller->moduleActions;
        }

        $added = array();

        foreach ($groups as $group) {
            foreach ($group as $action) {
                $url = $this->_extractActionUrl($action);
                $actionPath = $url['controller'] . '/' . $url['action'];
                $added[$actionPath] = $action;
            }
        }

        return $added;
    }

    public function outputActions($actions, $options = array()) {
        $this->options = $this->_mergeOptions($options);
        $this->currentController = $this->_foundCurrentController();
        $this->targetController = $this->_foundTargetController();
        return $this->_outputActions($actions);
    }

    private function _outputActions($actions) {
        switch ($this->options['layout']) {
            case self::LAYOUT_LIST:
                return $this->_outputActionsLine($actions);

            case self::LAYOUT_TABLE:
                return $this->_outputActionsTable($actions);

            default:
                throw new Exception("Layout not mapped: \"{$this->options['layout']}\".");
        }
    }

    private function _outputActionsLine($actions) {
        $buffer = $this->options['beforeAll'];
        foreach ($actions as $action) {
            $buffer .= $this->options['beforeEach'] . $this->_buildActionLink($action) . $this->options['afterEach'];
        }
        $buffer .= $this->options['afterAll'];
        return $buffer;
    }

    private function _outputActionsTable($actions) {
        if (count($actions) >= 4) {
            $rows = floor(count($actions) / sqrt(count($actions)));
        } else {
            $rows = 1;
        }

        $columns = ceil(count($actions) / $rows);
        $cellWidth = ($columns == 0 ? '100%' : floor(100 / $columns) . '%');

        $b = $this->options['tableLayoutBeforeAll'];
        $b .= '<table class="actionListHelperTableLayout">';
        $cell = 0;

        for ($row = 0; $row < $rows; $row++) {
            $b .= '<tr>';
            for ($column = 0; $column < $columns; $column++) {
                $index = $row * $columns + $column;
                $b .= "<td style='width: $cellWidth'>";
                if (!empty($actions[$index])) {
                    $b .= $this->options['tableLayoutBeforeEach'];
                    $b .= $this->_buildActionLink($actions[$index]);
                    $b .= $this->options['tableLayoutAfterEach'];
                } else {
                    $b .= '&nbsp;';
                }

                $b .= '</td>';
            }
            $b .= '</tr>';
        }

        $b .= '</table>';
        $b .= $this->options['tableLayoutAfterAll'];
        return $b;
    }

    public function setDefaultOption($key, $value) {
        $this->defaultOptions[$key] = $value;
    }

    private function _mergeOptions($options) {
        foreach ($this->defaultOptions as $key => $defaultValue) {
            if (!isset($options[$key])) {
                $options[$key] = isset($this->settings[$key]) ? $this->settings[$key] : $defaultValue;
            }
        }

        return $options;
    }

    private function _foundCurrentController() {
        return $this->_getController($this->params['controller']);
    }

    private function _foundTargetController() {
        if ($this->options['controller']) {
            return $this->_getController($this->options['controller']);
        } else {
            return $this->currentController;
        }
    }

    private function _getController($controllerName) {
        return $this->CakeLayers->getController($controllerName);
    }

    private function _buildActionLink($action) {
        $linkOptions = empty($action['linkOptions']) ? array() : $action['linkOptions'];        
        $linkOptions['method'] = $this->_isActionPost($action) ? 'post' : 'get';
        $question = isset($action['question']) ? __($action['question'], true) : false;
        return $this->AccessControl->link(
                        $this->_getTitle($action), $this->_buildActionUrl($action), $linkOptions, $question);
    }

    private function _isActionPost($action) {
        return !empty($action['post']);
    }

    private function _isNavigable($targetAction) {
        $message = $this->_checkNavigable($targetAction);
        if ($message == false) {
            return true;
        } else {
            if ($this->debug) {
                debug(array('notNavigable' => compact('message', 'targetAction')));
            }

            return false;
        }
    }

    private function _checkNavigable($targetAction) {
        $actionUrl = $this->_extractActionUrl($targetAction);

        //Acesso negado
        if (!$this->AccessControl->hasAccessByUrl($this->_buildActionUrl($targetAction))) {
            return __('Access denied.', true);
        }

        $currentUrl = $this->_extractCurrentUrl();

        // Mesma action
        if (($currentUrl['controller'] == $actionUrl['controller']) &&
                ($currentUrl['action'] == $actionUrl['action'])) {
            return __('Same controller/action.', true);
        }

        // Formato curto
        if ($this->options['shortFormat'] && !empty($targetAction['skipOnShort'])) {
            return __('Skip on short format menu.', true);
        }

        // Não tem ID requerido
        if ($targetAction['hasId'] && $actionUrl['id'] == null) {
            return __('Has not required id.', true);
        }

        // Não necessita de ID e opção para excluir
        if (!$targetAction['hasId'] && $this->options['skipNoRequiredIdActions']) {
            return __('Skip no required id actions.', true);
        }

        // Negado pelo controller
        if (($actionUrl['controller'] == $this->_getTargetControllerUri()) &&
                isset($this->targetController->notModuleActions) &&
                array_search($actionUrl['action'], $this->targetController->notModuleActions) !== false
        ) {
            return __('Denied by controller.', true);
        }

        // Registrado na opção skipActions
        foreach ($this->options['skipActions'] as $skipActionUrl) {
            if ($this->_isUrlEquals($this->_parseUrl($skipActionUrl), $actionUrl)) {
                return __('Action skipped.', true);
            }
        }

        // Método próprio no controller
        if (!$this->_isNavigableControllerMethod($targetAction)) {
            return __('Navigable method returned false.', true);
        }

        return false;
    }

    private function _buildActionUrl($targetAction) {
        $actionUrl = $this->_extractActionUrl($targetAction);

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

        return $url;
    }

    private function _getTitle($action) {
        $actionUrl = $this->_extractActionUrl($action);

        if ($this->options['shortFormat']) {
            return trim(sprintf($action['format'], ''));
        } else {
            $title = Inflector::singularize($actionUrl['controller']);
            $title = !empty($action['plural']) ? $actionUrl['controller'] : Inflector::singularize($actionUrl['controller']);
            $title = Inflector::humanize($title);
            $title = str_replace(' ', '', $title);
            return trim(sprintf($action['format'], __($title, true)));
        }
    }

    private function _extractCurrentUrl() {
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
            'id' => ($this->options['id'] ? $this->options['id'] : $id )
        );
    }

    private function _getTargetControllerUri() {
        return Inflector::underscore($this->targetController->name);
    }

    private function _getTargetControllerPluginUri() {
        return empty($this->targetController->plugin) ? null : Inflector::underscore($this->targetController->plugin);
    }

    private function _extractActionUrl($action) {
        $actionUrl = $this->_parseUrl($action['url']);

        $url = array(
            'plugin' => $actionUrl['plugin'] ? $actionUrl['plugin'] : $this->_getTargetControllerPluginUri(),
            'controller' => $actionUrl['controller'] ? $actionUrl['controller'] : $this->_getTargetControllerUri(),
            'action' => isset($actionUrl['action']) ? $actionUrl['action'] : 'index',
            'id' => $this->_extractActionId($action)
        );

        return $url;
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

    private function _extractActionId($action) {
        $currentUrl = $this->_extractCurrentUrl();

        if ($action['hasId']) {
            if (isset($action['field']) && $action['field']) {
                $obj = $this->_getTargetControllerObject($currentUrl['id']);
                $model = $this->_getTargetObjectModel();
                if ($obj && $model) {
                    return $this->_getObjectFieldValue($model->name, $obj, $action['field']);
                } else {
                    return $this->_getCurrentActionFieldValue($action['field']);
                }
            } else {
                return $currentUrl['id'];
            }
        } else {
            return null;
        }
    }

    private function _getTargetObjectModel() {
        return $this->targetController->{$this->targetController->modelClass};
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

    private function _isNavigableControllerMethod($targetAction) {
        $actionUrl = $this->_extractActionUrl($targetAction);
        $methodName = '__' . Inflector::variable("is_navigable_" . $actionUrl['action']);
        $controller = $this->_getController($actionUrl['controller']);

        if (method_exists($controller, $methodName)) {
            return call_user_func(array($controller, $methodName), array(
                        'options' => $this->options,
                        'currentUrl' => $this->_extractCurrentUrl(),
                        'targetAction' => $targetAction,
                        'targetActionUrl' => $actionUrl,
                    ));
        } else {
            return true;
        }
    }

}

?>
