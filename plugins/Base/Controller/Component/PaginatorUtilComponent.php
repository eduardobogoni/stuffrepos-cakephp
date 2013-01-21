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

App::import('Lib', 'Base.ArrayUtil');

class PaginatorUtilComponent extends Component {

    public $components = array('Session');
    private $filters = array();

    public function __construct(ComponentCollection $collection, $settings = array()) {
        $settings = array_merge($this->settings, (array) $settings);
        $this->Controller = $collection->getController();
        parent::__construct($collection, $settings);
    }

    public function startup(&$controller) {
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
    private function getAllFilters(&$controller) {
        $filtersData = array();

        if (isset($controller->paginatorUtil['listFilters'])
                && is_array($controller->paginatorUtil['listFilters'])) {
            $filtersData = array_merge($filtersData, $controller->paginatorUtil['listFilters']);
        }

        if (method_exists($controller, 'getPaginatorUtilFilters')) {
            $filtersData = array_merge(
                    $filtersData, $controller->getPaginatorUtilFilters()
            );
        }

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

    public function beforeRender(&$controller) {
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
        return isset($this->options[$config])
                && $this->options[$config];
    }

    /**
     *
     * @param string $config
     * @return mixed
     */
    private function getConfig($config) {
        if ($this->hasConfig($config)) {
            return $this->options[$config];
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
                $this->hasConfig('valuesFunction') ||
                $this->hasConfig('conditionsFunction') ||
                $this->hasConfig('conditionsPerValue');
    }

    /**
     *
     * @return array
     */
    public function getValuesList() {
        if ($this->hasConfig('valuesFunction')) {
            return $this->controller->{$this->getConfig('valuesFunction')}();
        } else if ($this->hasConfig('values')) {
            return ArrayUtil::keysAsValues(
                            array_keys($this->getConfig('values'))
            );
        } else if ($this->hasConfig('conditionsPerValue')) {
            return ArrayUtil::keysAsValues(
                            array_keys($this->getConfig('conditionsPerValue'))
            );
        } else if ($this->hasConfig('conditionsFunction')) {
            return ArrayUtil::keysAsValues(
                            $this->controller->{$this->getConfig('conditionsFunction')}()
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
        } else if ($this->hasConfig('conditionsFunction') || $this->hasConfig('conditionsPerValue')) {
            if ($this->hasConfig('conditionsFunction')) {
                $conditions = $this->controller->{$this->getConfig('conditionsFunction')}();
            } else { // if ($this->hasConfig('conditionsPerValue'))                
                $conditions = $this->getConfig('conditionsPerValue');
            }

            $value = $this->getValue();
            if ($value !== null) {
                if (isset($conditions[$value])) {
                    return $conditions[$value];
                } else {
                    return null;
                }
            } else {
                return $value;
            }
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
                return trim($this->controller->params['url'][$this->getSlugedName()]);
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
            'type' => 'text'
        );

        if ($this->isInputSelectType()) {
            $field['options'] = $this->getValuesList();
            $field['type'] = 'select';
            if (!$this->hasFilterDefaultValue()) {
                $field['empty'] = 'NÃO SELECIONADO';
            }
        }

        if ($this->hasConfig('fieldOptions')) {
            $field = array_merge($this->getConfig('fieldOptions'), $field);
        }

        return $field;
    }

    public function hasConditionValue() {
        return !$this->hasConfig('conditionsFunction') && !$this->hasConfig('conditionsPerValue');
    }

}

?>