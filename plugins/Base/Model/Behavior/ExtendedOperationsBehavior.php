<?php

/**
 * Operações comuns de model que geram exceção caso não sejam executadas
 * com sucesso (As operações nativas correspondentes retornam com valor
 * falso):
 * <ul>
 *  <li>saveOrThrowException</li>
 *  <li>saveAllOrThrowException</li>
 *  <li>findByIdOrThrowException</li>
 * </ul>
 * 
 * Como em controllers, utilize o atributo \$uses para autocarregamento
 * de modelos:
 * <pre>
 * class MyModel {
 * 
 *  public $actsAs = array(
 *      'Base.ExtendedOperations',
 *  );  
 * 
 *  public $uses = array(
 *      'OtherModel',
 *  );
 * 
 * }
 * 
 * $myModel = ClassRegistry::init('MyModel');
 * $myModel->OtherModel->find(...);
 * </pre>
 */
class ExtendedOperationsBehavior extends ModelBehavior {
    
    public function setup(\Model $model, $config = array()) {
        parent::setup($model, $config);
        $this->_setupUses($model);
    }

    public function beforeFind(\Model $model, $query) {
        parent::beforeFind($model, $query);
        $this->applyVirtualFields($model);
    }
    
    public function getVirtualFieldQuery(\Model $model, $virtualFieldName) {
        $this->applyVirtualFields($model);
        return $model->virtualFields[$virtualFieldName];
    }

    public function applyVirtualFields(\Model $model) {
        if (empty($model->virtualFieldsSetted)) {
            if (method_exists($model, 'getVirtualFields')) {
                foreach($this->_parseVirtualFields($model) as $name => $schema) {
                    $model->virtualFields[$name] = $schema['query'];
                    unset($schema['query']);
                    if (!isset($model->virtualFieldsSchema)) {
                        $model->virtualFieldsSchema = array();
                    }
                    $model->virtualFieldsSchema[$name] = $schema;
                }
            }
            $model->virtualFieldsSetted = true;
        }
    }

    public function saveOrThrowException(\Model $model, $data = null, $validate = true, $fieldList = array()) {
        if (!$model->save($data, $validate, $fieldList)) {
            throw new Exception("{$model->name} não foi salvo. " . print_r(array(
                'parameters' => compact('data', 'validade', 'fieldList'),
                '$model->data' => $model->data,
                '$model->validationErrors' => $model->validationErrors
                    ), true));
        }
    }

    public function saveAllOrThrowException(\Model $model, $data = array(), $options = array()) {
        if (!$model->saveAll($data, $options)) {
            throw new Exception("{$model->name} não foi salvo. " . print_r(array(
                'parameters' => compact('data', 'validade', 'fieldList'),
                '$model->data' => $model->data,
                '$model->validationErrors' => $model->validationErrors
                    ), true));
        }
    }

    public function findByIdOrThrowException(\Model $model, $id) {
        $row = $model->find(
                'first', array(
            'conditions' => array(
                "{$model->alias}.{$model->primaryKey}" => $id
            )
        ));
        if (empty($row)) {
            throw new Exception("{$model->name} não foi recuperado com o ID=$id.");
        } else {
            return $row;
        }
    }
    
    private function _parseVirtualFields(\Model $model) {
        $ret = array();
        foreach($model->getVirtualFields() as $name => $data) {
            if (!is_array($data)) {
                $data = array(
                    'query' => $data
                );
            }
            $ret[$name] = $data;
        }
        return $ret;
    }
    
    private function _setupUses(\Model $model) {
        if (isset($model->uses)) {
            foreach($model->uses as $modelName) {
                $model->{$modelName} = ClassRegistry::init($modelName);
            }
        }
    }

}
