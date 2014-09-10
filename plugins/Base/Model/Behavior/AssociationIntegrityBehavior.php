<?php

class AssociationIntegrityBehavior extends ModelBehavior {

    public function beforeValidate(\Model $model, $options = array()) {
        parent::beforeValidate($model, $options);
        foreach ($model->belongsTo as $alias => $config) {
            $this->__setupAssociationValidate($model, $alias, $config['foreignKey']);
            $model->data[$model->alias][$config['foreignKey']] = $this->__currentFieldValue($model, $config['foreignKey']);
        }
    }

    private function __currentFieldValue(\Model $model, $field) {
        if (array_key_exists($model->alias, $model->data) && array_key_exists($field, $model->data[$model->alias])) {
            return $model->data[$model->alias][$field];
        }
        if (array_key_exists($model->alias, $model->data) && array_key_exists($model->foreignKey, $model->data[$model->alias])) {
            $id = $model->data[$model->alias][$this->foreignKey];
        } else {
            $id = $model->id;
        }
        if ($id) {
            $row = $model->find('first', array(
                'conditions' => array(
                    "{$model->alias}.{$model->primaryKey}" => $id,
                )
            ));
            if (empty($row)) {
                return null;
            } else {
                return $row[$model->alias][$field];
            }
        } else {
            return null;
        }
    }

    private function __setupAssociationValidate(\Model $model, $alias, $foreignKey) {
        if (empty($model->validate[$foreignKey][__CLASS__])) {
            $model->validate[$foreignKey][__CLASS__] = $this->__foreignKeyValidation(
                    $model
                    , $alias
                    , $foreignKey
            );
        }
    }

    private function __foreignKeyValidation(\Model $model, $associationAlias, $foreignKey) {
        return array(
            'rule' => array('validateForeignKey', $associationAlias),
            'message' => 'Associação é vazia ou não existe',
            'allowEmpty' => $this->__allowEmpty($model, $foreignKey),
            'required' => true,
        );
    }

    private function __allowEmpty(\Model $model, $foreignKey) {
        $schema = $model->schema();
        return $schema[$foreignKey]['null'];
    }

    public function validateForeignKey(\Model $model, $check, $associationAlias) {
        foreach ($check as $field => $value) {
            if (!$value) {
                return false;
            }
            $key = $associationAlias . '.' . $model->{$associationAlias}->primaryKey;
            $associationRow = $model->{$associationAlias}->find('first', array(
                'conditions' => array($key => $value)
            ));
            if (empty($associationRow)) {
                return false;
            }
        }
        return true;
    }

}
