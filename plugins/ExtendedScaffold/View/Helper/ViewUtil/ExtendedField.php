<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ExtendedField {

    const LABEL_WIDTH_PERCENT = 0.4;

    private $name;
    private $options;

    /**
     *
     * @var ExtendedLine
     */
    private $parent;

    public function __construct(ExtendedLine $parent, $data) {
        $this->parent = $parent;
        $this->name = $data['name'];
        $this->options = $data['options'];
    }

    public function output() {
        $b = '';

        $b .= '<th style="width: ' . $this->parent->getParent()->getFieldLabelWidth() . "\">\n";
        $b .= $this->_getLabel() . ':';
        $b .= "\n</th>\n";

        $b .= '<td';
        $b .= ' style="width: ' . $this->_getInputWidth() . '"';
        $b .= ' colspan="' . $this->_getInputColspan() . '"';
        $b .= ">\n";
        $b .= $this->_getValue();
        $b .= "\n</td>\n";

        return $b;
    }

    /**
     *
     * 
     * @param type $currentLineFieldCount
     * @param type $linesMaxFieldCount
     * @return type 
     */
    private function _getInputColspan() {
        return $this->parent->getFieldInputColspan();
    }

    private function _getInputWidth() {
        return $this->parent->getParent()->getFieldInputWidth();
    }

    private function _getLabel() {
        if (!empty($this->options['label'])) {
            return $this->options['label'];
        } else {
            $associations = &$this->parent->getParent()->getAssociations();

            if (!empty($associations['belongsTo'])) {
                foreach ($associations['belongsTo'] as $_alias => $_details) {
                    if ($this->name === $_details['foreignKey']) {
                        return __($_alias, true);
                    }
                }
            }
            return __(Inflector::humanize($this->_getFieldName()), true);
        }
    }

    private function _getType() {
        $fieldInfo = $this->parent->getParent()->getParent()->CakeLayers->getFieldSchema(
                $this->_getFieldName(), $this->_getModelClass()
        );
        if (empty($fieldInfo['type'])) {
            return false;
        } else {
            return $fieldInfo['type'];
        }
    }

    private function _getModelClass() {
        $nameParts = Basics::fieldNameToArray($this->name);
        if (count($nameParts) > 1) {
            return $nameParts[0];
        } else {
            return $this->parent->getParent()->getModelClass();
        }
    }

    private function _getFieldName() {
        $nameParts = Basics::fieldNameToArray($this->name);
        if (count($nameParts) > 1) {
            return $nameParts[1];
        } else {
            return $nameParts[0];
        }
    }

    private function _getPath() {
        $path = explode('.', $this->name);
        if (count($path) == 1) {
            return array(
                $this->parent->getParent()->getModelClass(),
                $path[0]
            );
        } else {
            return $path;
        }
    }

    private function _mask() {
        return empty($this->options['mask']) ? null : $this->options['mask'];
    }

    private function _getValue() {

        $ViewUtilHelper = &$this->parent->getParent()->getParent();

        $instance = &$this->parent->getParent()->getInstance();
        $associations = &$this->parent->getParent()->getAssociations();

        if (!empty($associations['belongsTo'])) {
            foreach ($associations['belongsTo'] as $_alias => $_details) {
                if ($this->name === $_details['foreignKey']) {
                    return $ViewUtilHelper->AccessControl->linkOrText(
                                    $instance[$_alias][$_details['displayField']], array(
                                'controller' => $_details['controller'],
                                'action' => 'view',
                                $ViewUtilHelper->autoFormat($instance[$_alias][$_details['primaryKey']])));
                }
            }
        }

        $fieldType = $this->_getType();
        $value = $ViewUtilHelper->CakeLayers->modelInstanceFieldByPath(
                $this->parent->getParent()->getModelClass()
                , $instance
                , $this->_getPath()
                , true
        );

        switch ($fieldType) {
            case 'boolean':
                return $ViewUtilHelper->yesNo($value);

            case 'string':
                if ($this->_mask()) {
                    return $ViewUtilHelper->stringMask($value, $this->_mask());
                } else {
                    return $value;
                }

            case 'float':
                return $ViewUtilHelper->decimal($value);

            default:
                return $ViewUtilHelper->autoFormat($value);
        }
    }

}
