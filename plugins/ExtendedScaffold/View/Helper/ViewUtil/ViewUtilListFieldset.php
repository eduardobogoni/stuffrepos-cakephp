<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ViewUtilListFieldset {

    private $legend;
    private $fields = array();
    private $scaffoldVars;
    private $parent;
    private $listAssociation;

    public function __construct(ViewUtilHelper $parent, $data, &$scaffoldVars) {
        $this->parent = $parent;
        $this->legend = empty($data['legend']) ? null : $data['legend'];
        foreach ($data['lines'] as $fields) {
            foreach ($fields as $field) {
                $this->fields[$field['name']] = $field['options'];
            }
        }
        $this->listAssociation = $data['listAssociation'];
        $this->scaffoldVars = &$scaffoldVars;
    }

    public function output() {
        $b = '';
        if (!empty($this->legend)) {
            $b .= "<h3>{$this->legend}</h3>";
        }

        $b .= $this->parent->Lists->rowsTable(
                $this->fields
                , $this->_rows()
                , array(
            'model' => $this->_model()
            , 'showActions' => false
            , 'controller' => $this->_controller()
                )
        );

        return $b;
    }

    private function _rows() {
        return ModelTraverser::value(
                        $this->parent->CakeLayers->getModel($this->scaffoldVars['modelClass'])
                        , $this->scaffoldVars['instance']
                        , $this->listAssociation
        );
    }

    private function _model() {
        return $this->parent->CakeLayers->modelAssociationModel(
                        $this->scaffoldVars['modelClass']
                        , $this->listAssociation
                        , true
        );
    }

    private function _controller() {
        $controller = $this->parent->CakeLayers->getController(
                Inflector::pluralize($this->_model()->name), false
        );
        if ($controller) {
            return $controller->name;
        } else {
            return null;
        }
    }

}
