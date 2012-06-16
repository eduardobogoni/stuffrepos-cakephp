<?php

class ListsHelper extends AppHelper {

    public $helpers = array(
        'StuffRepos.ActionList',
        'Paginator',
        'StuffRepos.PaginatorUtil',
        'StuffRepos.AccessControl',
        'StuffRepos.ViewUtil',
        'StuffRepos.CakeLayers',
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
        if (empty($options['controller'])) {
            $this->controller = $this->CakeLayers->getController();
        } else {
            $this->controller = $this->CakeLayers->getController($options['controller']);
        }

        if (empty($options['model'])) {
            $this->model = $this->CakeLayers->getControllerDefaultModel($this->controller->name);
        } else {
            $this->model = $this->CakeLayers->getModel($options['model']);
        }

        $this->firstColumnViewLink = isset($options['firstColumnViewLink']) ? $options['firstColumnViewLink'] : true;

        $this->showActions = (isset($options['showActions']) ? $options['showActions'] : true);
        $this->showOrderNumbers = (isset($options['showOrderNumbers']) ? $options['showOrderNumbers'] : false);

        $fields = $this->_extractFields($fields);
        $b = "<table>\n";
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
        /*
          $this->Paginator->counter(array(
          'format' => __d('cake', 'Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
          )); */

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
            $path = array(
                $this->model->alias,
                $nameParts[0]
            );
            $model = &$this->model;
            assert('$model instanceof Model');
        } else {
            $path = $nameParts;
            if ($nameParts[0] == $this->model->alias) {
                $model = &$this->model;
                assert('$model instanceof Model');
            } else {
                $model = &$this->model->{$nameParts[0]};
                assert('$model instanceof Model');
            }
        }

        $modelSchema = $model->schema();

        if (!empty($modelSchema[$path[1]])) {
            $type = $modelSchema[$path[1]]['type'];
        } else if (!empty($model->virtualFieldsSchema[$path[1]]['type'])) {
            $type = $model->virtualFieldsSchema[$path[1]]['type'];
        }

        return compact('staticValue', 'name', 'path', 'type', 'emptyValue', 'label', 'association', 'align', 'valueFunction', 'extraData', 'mask');
    }

    private function _fieldAssociation($field) {
        $associations = $this->_modelAssociations();
        if (!empty($associations['belongsTo'])) {
            foreach ($associations['belongsTo'] as $_alias => $_details) {
                if ($field === $_details['foreignKey']) {
                    return mergeArrayWithKeys(array('alias' => $_alias), $_details);
                }
            }
        }

        return null;
    }

    private function _rowLine($fields, $row, $rowIndex) {

        $class = null;
        if ($rowIndex % 2 == 0) {
            $class = ' class="altrow"';
        }
        $b = "\n\t<tr{$class}>\n";

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
        return $this->CakeLayers->getModelAssociations($this->model);
    }

    public function _fieldLabel($field) {
        $title = null;
        if (!empty($field['label'])) {
            $title = $field['label'];
        }

        $key = $field['path'][count($field['path']) - 1];

        if (isset($this->params['paging'][$this->model->name])) {
            if (empty($title)) {
                return $this->Paginator->sort($key);
            } else {
                return $this->Paginator->sort($title, $key);
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
        if (!empty($field['association'])) {
            $modelIndex = $field['association']['alias'];
            $fieldIndex = $field['association']['displayField'];
            $link = array('controller' => $field['association']['controller'], 'action' => 'view',
                $row[$field['association']['alias']][$field['association']['primaryKey']]);
        } else {
            if ($firstField && $this->firstColumnViewLink) {
                $link = array(
                    'controller' => Inflector::underscore($this->controller->name),
                    'action' => 'view',
                    $row[$this->model->alias][$this->model->primaryKey]
                );
            }
            list($modelIndex, $fieldIndex) = $field['path'];
        }

        if ($field['valueFunction']) {
            $value = $this->_formatValueByFunction($field, $row);
        } else if ($field['staticValue'] !== null) {
            $value = $field['staticValue'];
        } else {
            $value = $this->CakeLayers->modelInstanceFieldByPath(
                    $this->model, $row, $field['path'], true
            );

            if (is_array($value)) {
                $value = $this->_formatValueAsList($value);
            } else {
                if (!empty($field['mask'])) {
                    $value = $this->ViewUtil->stringMask($value, $field['mask']);
                } else {
                    switch ($field['type']) {
                        case $this->ViewUtil->VALUE_TYPE_BOOLEAN:
                            $value = $this->ViewUtil->yesNo($value);
                            break;

                        default:
                            $value = $this->ViewUtil->autoFormat($value);
                    }
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
            return ($paging['page'] - 1) * ($paging['limit']);
        } else {
            return 0;
        }
    }

}

?>
