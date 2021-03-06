<?php

/*
 * Copyright 2010 Eduardo H. Bogoni <eduardobogoni@gmail.com>
 *
 * This file is part of CakePHP Bog Util.
 *
 * CakePHP Bog Util is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * CakePHP Bog Util is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * CakePHP Bog Util. If not, see http://www.gnu.org/licenses/.
 */

App::uses('ArrayUtil', 'Base.Lib');
App::uses('Basics', 'Base.Lib');

/**
 * Permite criar filtros nas listagens de registros (Páginas "index").
 */
class PaginatorUtilComponent extends Component {

    public $components = array('Session');
    private $filters = array();

    public function __construct(ComponentCollection $collection, $settings = array()) {
        $settings = array_merge($this->settings, (array) $settings);
        $this->Controller = $collection->getController();
        parent::__construct($collection, $settings);
    }

    public function startup(\Controller $controller) {
        parent::startup($controller);
        if ($controller->params['action'] == 'index') {
            $this->startupIndex($controller);
        }
    }

    private function startupIndex(&$controller) {
        foreach ($this->getAllFilters($controller) as $filter) {
            $value = $filter->getValue();
            $condition = $filter->getCurrentCondition();


            if ($value !== null && $condition) {
                $this->Controller->paginate['conditions'][] =
                        $filter->hasConditionValue() ? array($condition => $value) : $condition;
            }

            $filter->writePersistentValue();
        }
    }

    /**
     *
     * @param <type> $controller
     * @return PaginatorUtilComponentFilter[]
     */
    private function getAllFilters(\Controller $controller) {
        $filtersData = method_exists($controller, 'getPaginatorUtilFilters') ?
                $controller->getPaginatorUtilFilters() :
                array();
        $filters = array();
        foreach ($filtersData as $filterName => $filterData) {
            $filters[] = $this->getFilter($controller, $filterName, $filterData);
        }
        return $filters;
    }

    private function getFilter(&$controller, $filterName, $filterOptions) {
        if (!isset($this->filters[$controller->name][$filterName])) {
            $this->filters[$controller->name][$filterName] = new PaginatorUtilComponentFilter(
                    $this, $controller, $filterName, $filterOptions
            );
        }
        return $this->filters[$controller->name][$filterName];
    }

    public function beforeRender(\Controller $controller) {
        parent::beforeRender($controller);
        if ($controller->params['action'] == 'index') {
            $this->beforeRenderIndex($controller);
        }
    }

    private function beforeRenderIndex(&$controller) {
        $fields = array();

        foreach ($this->getAllFilters($controller) as $filter) {
            $fields[$filter->getName()] = $filter->buildField();
        }

        $controller->request->params['paginatorUtil']['filterFields'] = $fields;
    }
}

class PaginatorUtilComponentFilter {

    private $controller;
    private $name;
    private $component;

    /**
     *
     * @param Component $component
     * @param Controller $controller
     * @param string $name
     * @param array $options 
     */
    public function __construct(&$component, &$controller, $name, $options) {
        $this->controller = $controller;
        $this->name = $name;
        $this->component = $component;
        $this->options = $options;
    }

    /**
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     *
     * @param string $config
     * @return boolean 
     */
    private function hasConfig($config) {
        return ArrayUtil::hasArrayIndex(
                        $this->options
                        , Basics::fieldNameToArray($config)
        );
    }

    /**
     *
     * @param string $config
     * @return mixed
     */
    private function getConfig($config) {
        if ($this->hasConfig($config)) {
            return ArrayUtil::arrayIndex($this->options, Basics::fieldNameToArray($config), true);
        } else {
            return null;
        }
    }

    /**
     *
     * @return boolean
     */
    public function isInputSelectType() {
        return $this->hasConfig('values') ||
                $this->hasConfig('conditionsPerValue');
    }

    /**
     *
     * @return array
     */
    public function getValuesList() {
        if ($this->hasConfig('values')) {
            return $this->getConfig('values');            
        } else if ($this->hasConfig('conditionsPerValue')) {
            return ArrayUtil::keysAsValues(
                            array_keys($this->getConfig('conditionsPerValue'))
            );
        } else {
            return null;
        }
    }

    public function getFilterDefaultValue() {
        return $this->getConfig('default');
    }

    public function hasFilterDefaultValue() {
        return $this->hasConfig('default');
    }

    public function getCurrentCondition() {
        if ($this->hasConfig('conditions')) {
            return $this->getConfig('conditions');
        } else if ($this->hasConfig('conditionsPerValue')) {
            $conditions = $this->getConfig('conditionsPerValue');
            $value = $this->getValue();            
            return $value !== null && isset($conditions[$value]) ?
                    $conditions[$value] :
                    null;
        } else {
            throw new Exception("Não foi definido uma condição para o filtro \"{$this->name}\" em \"{$this->controller->name}\".");
        }
    }

    public function getValue() {
        $update = isset($this->controller->params['url']['_update']) ? $this->controller->params['url']['_update'] : false;
        $clear = isset($this->controller->params['url']['_clear']) ? $this->controller->params['url']['_clear'] : false;

        $default = null;
        if ($this->hasFilterDefaultValue()) {
            $default = $this->getFilterDefaultValue();
        }

        if ($update) {
            if (isset($this->controller->params['url'][$this->getSlugedName()]) && trim($this->controller->params['url'][$this->getSlugedName()]) !== '') {
                $value = trim($this->controller->params['url'][$this->getSlugedName()]);
                if ($this->getConfig('fieldOptions.type') == 'date') {
                    if (strtotime($value) === false) {
                        return $default;
                    }
                }
                return $value;
            } else {
                return $default;
            }
        } else if ($clear) {
            return $default;
        } else {
            return $this->readPersistentValue();
        }
    }

    private function getSlugedName() {
        return Inflector::slug($this->name);
    }

    private function getSessionPath() {
        return implode(
                '.', array(
            'PaginatorUtil',
            $this->controller->name,
            $this->controller->params['action'],
            $this->getSlugedName()
                )
        );
    }

    public function writePersistentValue() {
        $this->component->Session->write(
                $this->getSessionPath(), $this->getValue()
        );
    }

    public function readPersistentValue() {
        return $this->component->Session->read($this->getSessionPath());
    }

    public function buildField() {
        $field = array(
            'value' => $this->getValue(),
            'type' => empty($this->options['type']) ? 'text' : $this->options['type']
        );

        if ($this->isInputSelectType()) {
            $field['options'] = $this->getValuesList();
            $field['type'] = 'select';
            if (!$this->hasFilterDefaultValue()) {
                $field['empty'] = 'NÃO SELECIONADO';
            }
        }

        if ($this->hasConfig('fieldOptions')) {
            $field = $this->getConfig('fieldOptions') + $field;
        }

        if (in_array($field['type'], array('date', 'time', 'datetime'))) {
            $field['selected'] = $field['value'];
        }

        return $field;
    }

    public function hasConditionValue() {
        return !$this->hasConfig('conditionsPerValue');
    }

}
