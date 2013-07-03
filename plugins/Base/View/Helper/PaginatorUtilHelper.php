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

class PaginatorUtilHelper extends AppHelper {

    public $helpers = array('Html', 'Base.ExtendedForm');

    public function filterForm() {

        if (!empty($this->params['paginatorUtil']['filterFields'])) {
            $submitOptions = array('type' => 'get', 'url' => $this->_updateUrl());

            $buffer = "<div class='filterForm'>";
            $buffer .= $this->ExtendedForm->create(null, $submitOptions);
            $buffer .= "<fieldset><legend>Filter</legend>";
            foreach ($this->params['paginatorUtil']['filterFields'] as $key => $options) {
                $buffer .= $this->ExtendedForm->input(Inflector::slug($key), array_merge(
                                        $options, array('label' => $key)
                                ));
            }

            $buffer .= $this->ExtendedForm->submit(__('Filter', true), array('name' => '_update'));
            $buffer .= $this->ExtendedForm->submit(__('Show All', true), array('name' => '_clear'));
            $buffer .= "</fieldset>";
            $buffer .= $this->ExtendedForm->end();
            $buffer .= "</div>";

            return $buffer;
        } else {
            return '';
        }
    }
    
    private function _updateUrl() {
            $url = Router::parse($this->request->url); 
            $named = empty($url['named']) ? array() : $url['named'];
            $url += $named;
            unset($url['named']);            
            $url['page'] = 1;            
            return Router::url($url, true) . '?_update=true';
    }

    public function link($label, $parameters, $linkOptions = array()) {
        $linkParameters = array();
        foreach ($this->params['paginatorUtil']['filterFields'] as $key => $options) {
            $value = isset($parameters[$key]) ? $parameters[$key] : $options['value'];
            if ($value) {
                $linkParameters[$key] = $value;
            }
        }

        $linkParameters['_update'] = 'true';
        $pairLinkParameters = array();

        foreach ($linkParameters as $key => $value) {
            $pairLinkParameters[] = "$key=$value";
        }

        return $this->Html->link($label, '?' . implode('&', $pairLinkParameters), $linkOptions);
    }

}

?>