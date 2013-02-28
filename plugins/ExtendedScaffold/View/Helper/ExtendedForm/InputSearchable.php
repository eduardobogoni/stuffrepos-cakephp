<?php

class InputSearchable {

    public function __construct(ExtendedFormHelper $parent, $fieldName, $options) {        
        $this->parent = $parent;
        $this->fieldName = $fieldName;
        $this->options = $options;
        $this->hiddenInputId = $this->parent->createNewDomId();
        $this->visibleInputId = $this->parent->createNewDomId();
    }

    public function output() {
        $b = $this->parent->hidden($this->fieldName, array('id' => $this->hiddenInputId));
        $b .= $this->_visibleInput();
        return $b;
    }

    private function _visibleInput() {
        $visibleOptions = $this->options;
        unset($visibleOptions['search']);
        $visibleOptions['id'] = $this->visibleInputId;
        $visibleOptions['initCallback'] = 'ExtendedFormHelper.InputSearchable.initCallback';
        $visibleOptions['initOptions'] = json_encode(array(
            'searchOptions' => $this->_searchOptions(),
            'initialId' => $this->_hiddenInputValue(),
            'initialLabel' => $this->_visibleInputValue(),
                ));
        return $this->parent->text(
                        $this->fieldName . '_search', $visibleOptions
        );
    }

    private function _hiddenInputValue() {
        $this->parent->setEntity($this->fieldName);
        if (($fieldValue = $this->parent->value())) {
            return $fieldValue['value'];
        } else {
            return '';
        }
    }

    private function _visibleInputValue() {
        if (($hiddenValue = $this->_hiddenInputValue())) {
            $model = $this->parent->CakeLayers->getModel($this->_searchOptions('modelName'));
            $instance = $model->findByPrimaryKey($hiddenValue);
            return $instance[$model->alias][$this->_searchOptions('displayField')];
        }

        return '';
    }

    private function _searchOptions($subOption = false) {
        if ($subOption) {
            if (!empty($this->options['search'][$subOption])) {
                return $this->options['search'][$subOption];
            } else {
                return false;
            }
        } else {
            return $this->options['search'];
        }
    }

}

?>
