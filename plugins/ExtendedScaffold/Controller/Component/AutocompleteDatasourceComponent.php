<?php

class AutocompleteDatasourceComponent extends Component {
    
    public function startup(Controller $controller) {
        parent::startup($controller);
        if (!empty($controller->params['url']['_autocompleteDatasource'])) {
            $modelName = empty($controller->params['url']['modelName']) ? $controller->modelClass : $controller->params['url']['modelName'];
            $Model = ClassRegistry::init($modelName);
            $term = empty($controller->params['url']['term']) ? '' : $controller->params['url']['term'];
            $id = empty($controller->params['url']['id']) ? '' : $controller->params['url']['id'];
            $queryField = empty($controller->params['url']['queryField']) ? $Model->displayField : $controller->params['url']['queryField'];
            $displayField = empty($controller->params['url']['displayField']) ? $queryField : $controller->params['url']['displayField'];
            $termInAnyPlace = empty($controller->params['url']['termInAnyPlace']) ? false : $controller->params['url']['termInAnyPlace'];

            $options = compact(
                    'term', 'queryField', 'displayField', 'termInAnyPlace', 'id'
            );

            if (empty($id)) {
                $data = $this->_query($Model, $options);
            } else {
                $data = $this->_queryById($Model, $options);
            }

            echo json_encode($data);
            exit;
        }        
    }

    private function _query(Model $Model, $options) {
        $rows = $Model->find(
                'all', array(
            'conditions' => $this->_queryConditions($Model, $options),
            'limit' => 50,
                )
        );

        $data = array();

        foreach ($rows as $row) {
            $data[] = $this->_packageRow($Model, $options, $row);
        }

        return $data;
    }

    private function _packageRow($Model, $options, $row) {
        return array(
            'id' => $row[$Model->alias][$Model->primaryKey],
            'value' => $row[$Model->alias][$options['displayField']],
            'label' => $row[$Model->alias][$options['displayField']],
        );
    }

    private function _queryConditions($Model, $options) {
        $conditions = array();

        if (empty($options['termInAnyPlace'])) {
            $conditions["lower({$Model->alias}.{$options['queryField']}) like lower(concat(?,'%'))"] = trim($options['term']);
        } else {
            $conditions["lower({$Model->alias}.{$options['queryField']}) like lower(concat('%',?,'%'))"] = trim($options['term']);
        }

        return $conditions;
    }

    private function _queryById($Model, $options) {
        $row = $Model->find(
                'all', array(
            'conditions' => array(
                "{$Model->alias}.{$Model->primaryKey}" => $options['id']
            )
                )
        );

        return $this->_packageRow($Model, $options, $row);
    }

}

?>
