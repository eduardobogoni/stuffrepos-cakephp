<?php

App::uses('ModelTraverser', 'Base.Lib');

class AssociationIntegrityBehavior extends ModelBehavior {

    public function beforeValidate(\Model $model, $options = array()) {
        parent::beforeValidate($model, $options);
        foreach ($model->belongsTo as $alias => $config) {
            $this->__setupAssociationValidate($model, $alias, $config['foreignKey']);
            $model->data[$model->alias][$config['foreignKey']] = $this->__currentFieldValue($model, $config['foreignKey']);
        }
    }

    public function beforeDelete(\Model $model, $cascade = true) {
        $parentResult = parent::beforeDelete($model, $cascade);
        if (!$parentResult) {
            return $parentResult;
        }
        foreach ($model->hasMany as $alias => $config) {
            if (!($cascade && $config['dependent']) && $this->__hasHasManyAssociations($model, $alias)) {
                return false;
            }
        }
        return true;
    }

    private function __hasHasManyAssociations(\Model $model, $alias) {
        return $model->{$alias}->hasAny(array(
                    $alias . '.' . $model->hasMany[$alias]['foreignKey'] => $model->id
        ));
    }

    private function __currentFieldValue(\Model $model, $field) {
        return ModelTraverser::_findField($model, $model->data, $field, false);        
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
                'conditions' => array($key => $value),
                'recursive' => -1,
            ));
            if (empty($associationRow)) {
                return false;
            }
        }
        return true;
    }

}
