<?php

App::import('Lib', 'Base.ModelTraverser');

class ListsHelper extends AppHelper {

    public $helpers = array(
        'Widgets.ActionList',
        'Paginator',
        'Base.PaginatorUtil',
        'AccessControl.AccessControl',
        'ExtendedScaffold.ViewUtil',
        'Base.CakeLayers',
    );
    private $model;
    private $controller;
    private $showActions;

    public function listElement($fields, $rows, $options = array()) {
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
        $b .= "\t<tr>\n";
        if ($this->showOrderNumbers) {
            $b.= "\t\t<th>\n";
            $b.= '#';
            $b.= "\t\t</th>\n";
        }
        foreach ($fields as $_field) {
            $b.= "\t\t<th>\n";
            $b.= $this->_fieldLabel($_field);
            $b.= "\t\t</th>\n";
        }
        if ($this->showActions) {
            $b .= "\t<th>" . __('Actions', true) . "</th>\n";
        }
        $b .= "\t</tr>\n";

        if (empty($rows)) {
            $b .= "\t<tr><td colspan='100%' style='text-align: center'><em>Nenhum registro foi encontrado.</em></td></tr>\n";
        } else {
            $i = 0;
            foreach ($rows as $row) {
                $b .= $this->_rowLine($fields, $row, $i++);
            }
        }
        $b .= "\n";
        $b .= '</table>';

        return $b;
    }

    public function rowLine($fields, $row, $rowIndex, $tableOptions = array(),$rowOptions = array()) {
        $this->_setup($tableOptions);
        return $this->_rowLine($this->_extractFields($fields), $row, $rowIndex,$rowOptions);
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
    }

    public function paginatorInfo() {
        $b = '<div class="paging">';
        if ($this->Paginator->hasPrev()) {
            $b .= "\t" . $this->Paginator->prev('<< ') . "\n";
            $b .= ' | ';
        }
        $b .= $this->Paginator->numbers(array('modulus' => 4, 'first' => 2, 'last' => 2)) . "\n";
        if ($this->Paginator->hasNext()) {
            $b .= ' | ';
            $b .= "\t " . $this->Paginator->next(' >>') . "\n";
        }


        $b .= $this->Paginator->counter(array(
            'format' => __('({:page}-{:pages}/{:count})', true)
                ));
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
        $path = null;
        $emptyValue = '&nbsp;';
        $label = null;
        $type = null;
        $align = null;
        $valueFunction = null;
        $extraData = null;
        $staticValue = null;
        $mask = null;

        if (is_array($value)) {
            $name = $key;
            foreach (array('staticValue', 'align', 'label', 'emptyValue', 'valueFunction', 'extraData', 'mask') as $option) {
                if (isset($value[$option])) {
                    ${$option} = $value[$option];
                }
            }
        } else {
            $name = $value;
        }

        $association = $this->_fieldAssociation($name);

        $nameParts = explode('.', $name);
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

        if ($model) {
            $modelSchema = $model->schema();

            if (!empty($modelSchema[$path[1]])) {
                $type = $modelSchema[$path[1]]['type'];
            } else if (!empty($model->virtualFieldsSchema[$path[1]]['type'])) {
                $type = $model->virtualFieldsSchema[$path[1]]['type'];
            }
        } else {
            $type = 'string';
        }

        return compact(
                        'staticValue'
                        , 'name'
                        , 'path'
                        , 'type'
                        , 'emptyValue'
                        , 'label'
                        , 'align'
                        , 'valueFunction'
                        , 'extraData'
                        , 'mask'
                        , 'association'
        );
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

        $class = null;
        if ($rowIndex % 2 == 0) {
            $class = ' class="altrow"';
        }
        $b = "\n\t<tr{$class}";
        
        if (!empty($options['htmlAttributes']) && is_array($options['htmlAttributes'])) {
            foreach ($options['htmlAttributes'] as $key => $value) {
                $b .= " $key='$value'";
            }
        }
        
        
        $b .= ">\n";

        if ($this->showOrderNumbers) {
            $b .= "\t\t<td style='text-align: center'>\n";

            $b .= ($this->_currentPageFirstRowIndex() + $rowIndex + 1);

            $b .= "\t\t</td>\n";
        }

        $first = true;

        foreach ($fields as $field) {

            $b .= $this->_rowField($field, $row, $first);

            $first = false;
        }

        if ($this->showActions) {
            $b .= "\t\t<td class=\"actions\">\n";

            $b .= $this->ActionList->outputObjectMenu($row, $this->controller->name);

            $b .= "\t\t</td>\n";
        }
        $b .= "\t</tr>\n";

        return $b;
    }

    private function _rowField($field, $row, $firstField) {
        if (empty($field['align'])) {
            if (empty($field['association'])) {
                switch ($field['type']) {
                    case 'boolean':
                        $align = 'center';
                        break;
                    case 'integer';
                    case 'float';
                        $align = 'right';
                        break;

                    case 'string':
                    default:
                        $align = 'left';
                }
            } else {
                $align = 'center';
            }
        } else {
            $align = $field['align'];
        }
        $b = "\t\t<td style='text-align: $align;'>\n\t\t\t";
        $b .= $this->_rowFieldValue($field, $row, $firstField);
        $b .= " \n\t\t</td>\n";

        return $b;
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
        } else {
            if (empty($title)) {
                $title = Inflector::humanize($key);
            }
            return __($title, true);
        }
    }

    private function _rowFieldValue($field, $row, $firstField) {
        $link = $value = null;

        if ($field['valueFunction']) {
            $value = $this->_formatValueByFunction($field, $row);
        } else if ($field['staticValue'] !== null) {
            $value = $field['staticValue'];
        } else {
            if (!empty($field['association'])) {
                $modelIndex = $field['association']['alias'];
                $fieldIndex = $field['association']['displayField'];
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

                if (count($field['path']) > 1) {
                    list($modelIndex, $fieldIndex) = $field['path'];
                } else {
                    $modelIndex = false;
                    list($fieldIndex) = $field['path'];
                }
            }
            
            $value = $this->CakeLayers->modelInstanceFieldByPath(
                $this->model, $row, $field['path'], true
            );

            if (is_array($value)) {
                $value = $this->_formatValueAsList($value);
            } else {
                App::import('Helper', 'ViewUtil');
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
