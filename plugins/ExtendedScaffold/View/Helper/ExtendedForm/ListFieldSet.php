<?php

class ListFieldSet {

    /**
     *
     * @var array
     */
    private $fieldsetData;

    /**
     *
     * @var ExtendedFormHelper
     */
    private $parent;

    public function __construct(ExtendedFormHelper $parent, $fieldsetData) {
        $this->parent = $parent;
        $this->fieldsetData = $fieldsetData;
        $this->javascriptVariable = 'ExtendedFormHelper_ListFieldset_' . $this->parent->createNewDomId();
        $this->tableId = $this->parent->createNewDomId();
        $this->newRowPrototypeId = $this->parent->createNewDomId();
    }

    public function output() {

        $b = '';
        if ($this->_legend()) {
            $b .= "<h3>{$this->_legend()}</h3>";
        }

        $b .= $this->_newRowPrototype();

        $b .= '<div class="actions">';
        $b .= $this->parent->Html->link(
                __('New', true), '#', array(
            'onclick' => <<<EOT
    {$this->javascriptVariable}.addRow();
    return false;
EOT
                )
        );
        $b .= '</div>';

        $b .= $this->parent->Lists->rowsTable(
                $this->_columns()
                , $this->_rows()
                , $this->_tableOptions()
        );

        $b .= $this->parent->javascriptTag(<<<EOT
{$this->javascriptVariable} = new ExtendedFormHelper.ListFieldSet(
    '{$this->tableId}',
    '{$this->newRowPrototypeId}',
    {$this->_lastRowIndex()}
);
EOT
        );

        return $b;
    }

    private function _tableOptions() {
        return array(
            'model' => $this->_model()
            , 'showActions' => false,
            'htmlAttributes' => array(
                'id' => $this->tableId,
            )
        );
    }

    private function _legend() {
        return empty($this->fieldsetData['legend']) ? null : $this->fieldsetData['legend'];
    }

    private function _instances() {
        $instances = array();

        if (!empty($this->parent->data[$this->_listAssociation()])) {
            if (is_array($this->parent->data[$this->_listAssociation()])) {
                foreach ($this->parent->data[$this->_listAssociation()] as $rowIndex => $instance) {
                    $instances[$rowIndex][$this->_listAssociation()] = $instance;
                }
            }
        }

        return $instances;
    }

    private function _rows() {
        $rows = array();
        foreach ($this->_instances() as $rowIndex => $instance) {
            $rows[] = array_merge(array(
                '_rowIndex' => $rowIndex
                    ), $instance);
        }
        return $rows;
    }

    private function _lastRowIndex() {
        $last = -1;
        foreach ($this->_rows() as $row) {
            if ($row['_rowIndex'] > $last) {
                $last = $row['_rowIndex'];
            }
        }
        return $last;
    }

    private function _model() {
        return $this->parent->CakeLayers->modelAssociationModel(
                        $this->_parentModel()
                        , $this->_listAssociation()
                        , true
        );
    }

    private function _listAssociation() {
        return $this->fieldsetData['listAssociation'];
    }

    private function _parentModel() {
        return $this->parent->model();
    }

    private function _fields() {
        $fields = array();
        foreach ($this->fieldsetData['lines'] as $line) {
            foreach ($line as $field) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    private function _columns() {
        $columns = array();
        foreach ($this->_fields() as $field) {
            $options = array(
                'valueFunction' => array($this, '_fieldColumnCallback'),
                'escapeHtml' => false,
                'extraData' => array(
                    'fieldOptions' => $field['options']
                )
            );
            if (!empty($field['options']['label'])) {
                $options['label'] = $field['options']['label'];
            }

            $columns[$field['name']] = $options;
        }

        $columns['_deleteButton'] = array(
            'label' => __('Actions', true),
            'valueFunction' => array($this, '_actionsColumnValueFunction'),
        );

        return $columns;
    }

    public function _fieldColumnCallback($view, $instance, $fieldData) {
        unset($fieldData['extraData']['fieldOptions']['label']);
        return $this->parent->input(
                        $this->_fieldName($instance, $fieldData['path'])
                        , array_merge(
                                array('label' => false), $fieldData['extraData']['fieldOptions']
                        )
        );
    }

    public function _actionsColumnValueFunction($view, $instance, $fieldData) {
        $b = '<div class="actions">';
        $b .= $this->parent->Html->link(
                __('Delete', true), '#', array(
            'onclick' => <<<EOT
   {$this->javascriptVariable}.removeRow(this);    
    return false;
EOT
                )
        );
        $b .= '</div>';

        $b .= $this->parent->hidden(
                $this->_fieldName(
                        $instance, array(
                    $this->_model()->alias,
                    $this->_model()->primaryKey
                        )
                )
        );

        return $b;
    }

    private function _fieldName($instance, $fieldPath) {
        $name = $fieldPath[0] . '.' . $instance['_rowIndex'];
        for ($i = 1; $i < count($fieldPath); ++$i) {
            $name .= '.' . $fieldPath[$i];
        }

        return $name;
    }

    private function _newRowPrototype() {
        $tableId = $this->parent->createNewDomId();
        $b = "<table id='$tableId' style='display: none'>";
        $b .= $this->parent->Lists->rowLine($this->_columns(), array(
            '_rowIndex' => '%rowIndex%',
                ), 0, $this->_tableOptions(), array(
            'htmlAttributes' => array(
                'id' => $this->newRowPrototypeId,
            )
                ));
        $b .= '</table>';

        $b .= $this->parent->javascriptTag(<<<EOT
$(document).ready(function(){
    {$this->javascriptVariable}.removeElementFromForm('#$tableId');
});
EOT
        );

        return $b;
    }

}

?>
