<?php

App::import('Lib', 'Base.Basics');

class CommonValidationBehavior extends ModelBehavior {

    const EMPTY_VALUE = '@EMPTY_VALUE@';

    private $modelSettingsDefault = array(
        'forceValidateEmpty' => array(),
    );

    public function setup(\Model $model, $config = array()) {
        parent::setup($model, $config);
        $this->settings[$model->name] = array_merge($this->modelSettingsDefault, $config);
    }

    public function beforeValidate(\Model $model, $options = array()) {
        if (!parent::beforeValidate($model, $options)) {
            return false;
        }
        foreach ($this->settings[$model->name]['forceValidateEmpty'] as $field) {
            if (empty($model->data[$model->alias][$field]) || trim($model->data[$model->alias][$field]) == '') {
                $model->data[$model->alias][$field] = self::EMPTY_VALUE;
            }
        }
        return true;
    }

    public function beforeSave(\Model $model, $options = array()) {
        if (!parent::beforeSave($model, $options)) {
            return false;
        }
        foreach ($this->settings[$model->name]['forceValidateEmpty'] as $field) {
            if ($model->data[$model->alias][$field] == self::EMPTY_VALUE) {
                $model->data[$model->alias][$field] = '';
            }
        }
        return true;
    }

    public static function isUniqueInContext(Model $model, $check, $contextFields = array()) {
        $contextFields = ArrayUtil::arraylize($contextFields);                

        foreach ($check as $field => $value) {
            $conditions = array(
                "{$model->alias}.$field" => $value,
            );

            foreach ($contextFields as $contextField) {
                $contextFieldValue = self::currentFieldValue(
                                $model
                                , $contextField
                );

                if ($contextFieldValue === null) {
                    return false;
                }                                

                $conditions[Basics::fieldFullName($contextField, $model->alias)] = $contextFieldValue;
            }

            $result = $model->find(
                    'first', compact('conditions')
            );                        

            if (!empty($result)) {
                if (empty($model->data[$model->alias][$model->primaryKey])) {
                    return false;
                } else {
                    return $model->data[$model->alias][$model->primaryKey] == $result[$model->alias][$model->primaryKey];
                }
            }
        }

        return true;
    }

    public static function foreignKey(Model $model, $check) {
        foreach ($check as $field => $value) {
            if (!$value) {
                return false;
            }
        }
        return true;
    }

    private static function currentFieldValue(Model $model, $contextField) {
        $dataField = Basics::fieldValue($model->data, $contextField, $model->alias);
        if ($dataField !== null) {
            return $dataField;
        }

        if (($primaryKeyValue = Basics::fieldValue($model->data, $model->primaryKey, $model->alias))) {
            $instance = $model->find(
                    'first'
                    , array(
                'conditions' => array(
                    "{$model->alias}.{$model->primaryKey}" => $primaryKeyValue
                )
                    )
            );

            if (($fieldValue = Basics::fieldValue($instance, $field, $model->alias))) {
                return $fieldValue;
            }
        }

        return null;
    }

}

?>
