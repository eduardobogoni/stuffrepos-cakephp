<?php

App::import('Lib', 'Base.ModelTraverser');
App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ListsHelper extends AppHelper {

    public $helpers = array(
        'Widgets.ControllerMenu',
        'Paginator',
        'Base.PaginatorUtil',
        'AccessControl.AccessControl',
        'ExtendedScaffold.ViewUtil',
        'Base.CakeLayers',
    );
    private $model;
    private $controller;
    private $showActions;
    
    private $options = array();
    
    private $defaultOptions = array(
        'paginatorPrevText' => '<< ',
        'paginatorNextText' => ' >>',
        'paginatorNumberSeparator' => ' | ',
        'paginatorHiddenDisabled' => true,
        'rowActionListOptions' => array()
    );

    /**
     * 
     * @var array
     */
    private $defaultField = array(
        'path' => null,
        'emptyValue' => '&nbsp;',
        'label' => null,
        'type' => null,
        'align' => null,
        'valueFunction' => null,
        'extraData' => null,
        'staticValue' => null,
        'mask' => null,
        'link' => null,
    );

    public function listElement($fields, $rows, $options = array()) {
        $this->_setup($options);
        $b = $this->PaginatorUtil->filterForm();
        $b .= $this->paginatorInfo();
        $b .= $this->rowsTable($fields, $rows, $options);
        $b .= $this->paginatorInfo();
        return $b;
    }

    public function rowsTable($fields, $rows, $options = array()) {
        $this->_setup($options);
        $fields = $this->_extractFields($fields);
        $b = "<table";
        foreach ($this->htmlAttributes as $key => $value) {
            $b .= " $key=\"$value\"";
        }
        $b .= ">\n";
        $b .= $this->_header($fields);
        if (empty($rows)) {
            $b .= "\t<tr><td colspan='100%' style='text-align: center'><em>Nenhum registro foi encontrado.</em></td></tr>\n";
        } else {
            $i = 0;
            foreach ($rows as $row) {
                $options['position'] = $i == 0 ? 'top' :
                        ($i == count($rows) - 1 ? 'bottom' : 'middle');
                $b .= $this->_rowLine($fields, $row, $i++, $options);
            }
        }
        $b .= "\n";
        $b .= '</table>';

        return $b;
    }

    private function _header($fields) {
        $columns = array();
        if ($this->showOrderNumbers) {
            $columns[] = '#';
        }
        foreach ($fields as $_field) {
            $columns[] = $this->_fieldLabel($_field);
        }
        if ($this->showActions) {
            $columns[] = __d('extended_scaffold','Actions');
        }

        $b = "\t<thead><tr>\n";
        for ($i = 0; $i < count($columns); ++$i) {
            if ($i == 0) {
                $class = 'left';
            }
            else if ($i == count($columns) - 1) {
                $class = 'right';
            }
            else {
                $class = 'center';
            }
            
            $b.= "\t\t<th scope='col' class='$class'>\n";
            $b .= $columns[$i];
            $b.= "\t\t</th>\n";
        }
        $b .= "\t</tr></thead>\n";
        return $b;
    }

    public function rowLine($fields, $row, $rowIndex, $tableOptions = array(), $rowOptions = array()) {
        $this->_setup($tableOptions);
        return $this->_rowLine($this->_extractFields($fields), $row, $rowIndex, $rowOptions);
    }

    public function _setup($options) {
        if (empty($options['controller'])) {
            $this->controller = $this->CakeLayers->getController();
        } else {
            $this->controller = $this->CakeLayers->getController($options['controller']);
        }

        if (isset($options['model'])) {
            if ($options['model']) {
                $this->model = $this->CakeLayers->getModel($options['model']);
            } else {
                $this->model = false;
            }
        } else {
            $this->model = $this->CakeLayers->getControllerDefaultModel($this->controller->name);
        }

        $this->firstColumnViewLink = isset($options['firstColumnViewLink']) ? $options['firstColumnViewLink'] : true;
        $this->htmlAttributes = isset($options['htmlAttributes']) ? $options['htmlAttributes'] : array();
        $this->showActions = (isset($options['showActions']) ? $options['showActions'] : true);
        $this->showOrderNumbers = (isset($options['showOrderNumbers']) ? $options['showOrderNumbers'] : false);
        $this->options = $options + $this->defaultOptions;
    }

    public function paginatorInfo() {
        $b = '<div class="paging">';
        if ($this->Paginator->hasPrev() || !$this->options['paginatorHiddenDisabled']) {
            $b .= "\t" . $this->Paginator->prev(
                    $this->options['paginatorPrevText'],  
                    array(), 
                    null, 
                    array('class' => 'disabled')
            ) . "\n";
            $b .= $this->options['paginatorNumberSeparator'];
        }
        $b .= $this->Paginator->numbers(array('modulus' => 4, 'first' => 2, 'last' => 2, 'separator' => $this->options['paginatorNumberSeparator'])) . "\n";
        if ($this->Paginator->hasNext() || !$this->options['paginatorHiddenDisabled']) {
            $b .= $this->options['paginatorNumberSeparator'];
            $b .= "\t" . $this->Paginator->next(
                    $this->options['paginatorNextText'],  
                    array(), 
                    null, 
                    array('class' => 'disabled')
            ) . "\n";
        }

        $b .= "<span class='counter'>";
        $b .= $this->Paginator->counter(array(
            'format' => __d('extended_scaffold','({:page}-{:pages}/{:count})')
                ));
        $b .= "</span>";
        $b .= '</div>';

        return $b;
    }

    private function _extractFields($fields) {
        $result = array();

        foreach ($fields as $key => $value) {
            $result[] = $this->_extractField($key, $value);
        }

        return $result;
    }

    private function _extractField($key, $value) {
        $field = is_array($value) ?
                array_merge(array('name' => $key), $value) :
                array('name' => $value);
        $field = Hash::merge($this->defaultField, $field);
        $field['association'] = $this->_fieldAssociation($field['name']);
        list($field['path'], $model) = $this->_fieldPathModel($field['name']);
        $field['type'] = $this->_fieldType($field['path'], $model);
        return $field;
    }

    private function _fieldPathModel($fieldName) {
        $nameParts = explode('.', $fieldName);
        if (count($nameParts) == 1) {
            if ($this->model) {
                $path = array(
                    $this->model->alias,
                    $nameParts[0]
                );
                $model = &$this->model;
            } else {
                $path = array($nameParts[0]);
                $model = false;
            }
        } else {
            $path = $nameParts;
            $model = &$this->model->{$nameParts[0]};
        }
        return array($path, $model);
    }

    private function _fieldType($path, \Model $model) {
        if ($model) {
            $modelSchema = $model->schema();
            if (!empty($modelSchema[$path[1]])) {
                return $modelSchema[$path[1]]['type'];
            } else if (!empty($model->virtualFieldsSchema[$path[1]]['type'])) {
                return $model->virtualFieldsSchema[$path[1]]['type'];
            }
        } else {
            return 'string';
        }
    }

    private function _fieldAssociation($field) {
        $associations = $this->_modelAssociations();
        if (!empty($associations['belongsTo'])) {
            foreach ($associations['belongsTo'] as $_alias => $_details) {
                if ($field === $_details['foreignKey']) {
                    return $_details + array('alias' => $_alias);
                }
            }
        }

        return null;
    }

    private function _rowLine($fields, $row, $rowIndex, $options = array()) {

        $class = array();
        if ($rowIndex % 2 == 0) {
            $class[] = 'altrow';
        }

        if (!empty($options['position'])) {
            $class[] = $options['position'];
        }

        if (!empty($class)) {
            $class = ' class="' . implode(' ', $class) . '"';
        } else {
            $class = '';
        }

        $b = "\n\t<tr{$class}";
        
        if (!empty($options['htmlAttributes']) && is_array($options['htmlAttributes'])) {
            foreach ($options['htmlAttributes'] as $key => $value) {
                $b .= " $key='$value'";
            }
        }

        $b .= ">\n";

        $cells = array();

        if ($this->showOrderNumbers) {
            $cells[] = array(
                'attributes' => array(
                    'style' => 'text-align: center'
                ),
                'content' => ($this->_currentPageFirstRowIndex() + $rowIndex + 1)
            );
        }

        $first = true;
        foreach ($fields as $field) {
            $cells[] = array(
                'attributes' => array(
                    'style' => 'text-align: '.$this->_fieldValueDefaultAlign($field),
                ),
                'content' => $this->_rowFieldValue($field, $row, $first)
            );
            $first = false;
        }

        if ($this->showActions) {
            $rowActionListOptions = $this->options['rowActionListOptions'];
            if (!empty($this->options['model']) && empty($rowActionListOptions['model'])) {
                $rowActionListOptions['model'] = $this->options['model'];
            }
            $rowActionListOptions['controller'] = $this->controller->name;
            $cells[] = array(
                'attributes' => array(
                    'class' => array('actions')
                ),
                'content' => $this->ControllerMenu->instanceMenu($row, $rowActionListOptions)
            );
        }

        for ($i = 0; $i < count($cells); ++$i) {
            if ($i == 0) {
                $cells[$i]['attributes']['class'][] = 'left';
            } else if ($i == count($cells) - 1) {
                $cells[$i]['attributes']['class'][] = 'right';
            } else {
                $cells[$i]['attributes']['class'][] = 'center';
            }
            
            $b .= "\t\t<td{$this->_parseAttributes($cells[$i]['attributes'])}>\n";
            $b .= $cells[$i]['content'];
            $b.= "\t\t</td>\n";
        }
        $b .= "\t</tr>\n";

        return $b;
    }

    private function _fieldValueDefaultAlign($field) {
        if (empty($field['align'])) {
            if (empty($field['association'])) {
                switch ($field['type']) {
                    case 'boolean':
                        return 'center';
                        break;
                    case 'integer';
                    case 'float';
                       return 'right';
                        break;

                    case 'string':
                    default:
                        return 'left';
                }
            } else {
                return 'center';
            }
        } else {
            return $field['align'];
        }
    }

    private function _modelAssociations() {
        if ($this->model) {
            return $this->CakeLayers->getModelAssociations($this->model);
        } else {
            return array();
        }
    }

    public function _fieldLabel($field) {
        $title = null;
        if (!empty($field['label'])) {
            $title = $field['label'];
        }

        $key = $field['path'][count($field['path']) - 1];

        if ($this->model) {
            if (!empty($this->params['paging'][$this->model->name])) {
                if (empty($title)) {
                    return $this->Paginator->sort($key);
                } else {
                    return $this->Paginator->sort($title, $key);
                }
            }
        }
        if (empty($title)) {
            $title = Inflector::humanize($key);
        }
        return __d('extended_scaffold',$title, true);
    }

    private function _rowFieldValue($field, $row, $firstField) {
        $link = $value = null;

        if ($field['valueFunction']) {
            $value = $this->_formatValueByFunction($field, $row);
        } else if ($field['staticValue'] !== null) {
            $value = $field['staticValue'];
        } else {
            if (!empty($field['association'])) {
                if (!empty($row[$this->model->alias][$field['association']['foreignKey']])) {
                    $link = array('controller' => $field['association']['controller'], 'action' => 'view',
                        $row[$this->model->alias][$field['association']['foreignKey']]);
                }
            } else {
                if ($this->model && $firstField && $this->firstColumnViewLink) {
                    $link = array(
                        'controller' => Inflector::underscore($this->controller->name),
                        'action' => 'view',
                        $row[$this->model->alias][$this->model->primaryKey]
                    );
                }
            }
            
            $value = $this->CakeLayers->modelInstanceFieldByPath(
                $this->model, $row, $field['path'], true
            );

            if (is_array($value)) {
                $value = $this->_formatValueAsList($value);
            } else {
                switch ($field['type']) {
                    case ViewUtilHelper::VALUE_TYPE_BOOLEAN:
                        $value = $this->ViewUtil->yesNo($value);
                        break;

                    default:
                        $value = $this->ViewUtil->autoFormat($value);
                }
            }
        }

        if (empty($value)) {
            return $field['emptyValue'];
        } else if (empty($link)) {
            return $value;
        } else {
            return $this->AccessControl->linkOrText($value, $link);
        }
    }

    private function _formatValueAsList($value) {
        if (empty($value)) {
            return '';
        } else {
            $b = '<ul>';
            foreach ($value as $item) {
                $b .= '<li>' . $item . '</li>';
            }

            $b .= '</ul>';
            return $b;
        }
    }

    public function _formatValueByFunction($field, $row) {
        return call_user_func($field['valueFunction'], $this->CakeLayers->getCurrentView(), $row, $field);
    }

    private function _currentPageFirstRowIndex() {
        if (isset($this->params['paging'][$this->model->name])) {
            $paging = $this->params['paging'][$this->model->name];
            return ($paging['page'] - 1) * ($paging['options']['limit']);
        } else {
            return 0;
        }
    }

}

?>
