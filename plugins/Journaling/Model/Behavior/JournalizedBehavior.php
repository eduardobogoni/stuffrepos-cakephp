<?php

App::import('Lib', 'StuffreposBase.ArrayUtil');

class JournalizedBehavior extends ModelBehavior {

    private $rowsBeforeSave = array();

    public function setup(\Model $model, $config = array()) {
        parent::setup($model, $config);
        $model->bindModel(
            array(
                'hasMany' => array(
                    'Journal' => array(
                        'className' => 'Journaling.Journal',
                        'foreignKey' => 'journalized_id',
                        'conditions' => array(
                            'Journal.journalized_type' => $model->name
                        ),
                        'dependent' => false,
                    )
                )
            ),
            false
        );
    }

    public function beforeSave(Model $model) {
        if (!parent::beforeSave($model)) {
            return false;
        }

        $this->storeRowPreviousValues($model);
    }

    public function afterSave(\Model $model, $created) {
        if (!parent::afterSave($model, $created)) {
            return false;
        }

        if ($created) {
            return $this->createJournal(
                    $model
                    , 'create'
                    , $this->emptyFields($model)
                    , $model->data
            );
        } else {
            return $this->createJournal(
                    $model
                    , 'update'
                    , $this->rowBeforeSave($model)
                    , $model->data
            );
        }
    }

    public function beforeDelete(\Model $model, $cascade = true) {
        if (!parent::beforeDelete($model, $cascade)) {
            return false;
        }

        $this->storeRowPreviousValues($model);
    }

    public function afterDelete(\Model $model) {
        parent::afterDelete($model);

        return $this->createJournal(
                $model
                , 'delete'
                , $this->rowBeforeSave($model)
                , $this->emptyFields($model)
        );
    }

    private function rowBeforeSave(Model $model) {
        if ($this->primaryKeyValue($model, false)) {
            $path = array($model->name, $this->primaryKeyValue($model));

            if (ArrayUtil::hasArrayIndex($this->rowsBeforeSave, $path)) {
                return ArrayUtil::arrayIndex($this->rowsBeforeSave, $path);
            } else {
                throw new Exception(
                    "Previous value not found. " . print_r(array(
                        'modelName' => $model->name,
                        'modelAlias' => $model->alias,
                        'rowsBeforeSave' => $this->rowsBeforeSave,
                        'primaryKeyValue' => $this->primaryKeyValue($model),
                        'path' => $path
                        ), true));
            }
        } else {
            throw new Exception("No primary key found. {$model->alias}: {$model->name}.");
        }
    }

    private function storeRowPreviousValues(Model $model) {
        if ($this->primaryKeyValue($model, false)) {
            $row = $model->find('first', array(
                'conditions' => array(
                    "{$model->alias}.{$model->primaryKey}" => $this->primaryKeyValue($model),
                )
                ));

            if (!$row) {
                throw new Exception("Row not found.");
            }

            $this->rowsBeforeSave[$model->name][$this->primaryKeyValue($model)] =
                $row;
        }
    }

    private function primaryKeyValue(Model $model, $required = true) {
        if (!empty($model->data[$model->alias][$model->primaryKey])) {
            return $model->data[$model->alias][$model->primaryKey];
        } else if (!empty($model->id)) {
            return $model->id;
        } else if ($required) {
            throw new Exception("{$model->alias} has no primary key current value.");
        } else {
            return null;
        }
    }

    /**
     * 
     * @param Model $model
     * @param string $type
     * @param array $oldValues
     * @param array $values
     * @return boolean
     * @throws Exception
     */
    private function createJournal(Model $model, $type, $oldValues, $values) {
        $diffValues = $this->diffValues($model, $oldValues, $values);

        if ($type == 'update' && empty($diffValues)) {
            return true;
        }

        $model->Journal->create();
        if (!$model->Journal->save(array(
                'Journal' => array(
                    'type' => $type,
                    'journalized_type' => $model->name,
                    'journalized_id' => $model->id,
                )
            ))) {
            throw new Exception("Could not save Journal. " . print_r($model->Journal->validationErrors, true));
        }

        foreach ($diffValues as $field => $change) {
            $model->Journal->JournalDetail->create();
            if (!$model->Journal->JournalDetail->save(array(
                    'JournalDetail' => array(
                        'property' => $field,
                        'old_value' => $change['old'],
                        'value' => $change['current'],
                        'journal_id' => $model->Journal->id,
                    )
                ))) {
                throw new Exception("Could not save JournalDetail. " . print_r($model->Journal->JournalDetail->validationErrors, true));
            }
        }

        return true;
    }

    public function diffValues(Model $model, $oldValues, $values) {
        $changedFields = array();

        foreach ($this->journalizedFields($model) as $field) {
            $index = array($model->alias, $field);
            $old = ArrayUtil::hasArrayIndex($oldValues, $index) ?
                ArrayUtil::arrayIndex($oldValues, $index) :
                null;
            $current = ArrayUtil::hasArrayIndex($values, $index) ?
                ArrayUtil::arrayIndex($values, $index) :
                $old;

            if ($old === null && $current === null) {
                $changed = false;
            } else if ($old === null || $current === null) {
                $changed = true;
            } else {
                $changed = $old != $current;
            }

            if ($changed) {
                $changedFields[$field] = compact('old', 'current');
            }
        }

        return $changedFields;
    }

    public function emptyFields(Model $model) {
        $fields = array();

        foreach ($this->journalizedFields($model) as $field) {
            $fields[$field] = null;
        }

        return array(
            $model->alias => $fields
        );
    }

    private function journalizedFields(Model $model) {
        $fields = array();
        foreach (array_keys($model->schema()) as $field) {
            switch ($field) {
                case $model->primaryKey:
                case 'created':
                case 'modified':
                case 'updated':
                    continue;

                default:
                    $fields[] = $field;
            }
        }
        return $fields;
    }

}