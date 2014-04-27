<?php

class ExtendedOperationsBehavior extends ModelBehavior {

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

}
