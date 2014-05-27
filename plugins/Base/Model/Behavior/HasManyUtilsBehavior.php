<?php

App::uses('ArrayUtil', 'Base.Lib');

class HasManyUtilsBehavior extends ModelBehavior {

    const SETTING_ASSOCIATIONS = 'associations';

    /**
     *
     * @var array
     */
    private $options;

    /**
     *
     * @var array
     */
    private $toRemoveAssociationIds = array();

    public function setup(\Model $model, $config = array()) {
        $this->options[$model->name] = $config;
    }

    public function afterFind(\Model $model, $results, $primary = false) {
        if ($results) {
            foreach ($model->hasMany as $alias => $association) {
                foreach (array_keys($results) as $k) {
                    if (!empty($results[$k][$alias]) && is_array($results[$k][$alias])) {
                        $results[$k][$alias] = $this->_applyAfterFind($model->{$alias}, $results[$k][$alias]);
                    }
                }
            }
        }

        return $results;
    }

    private function _applyAfterFind(\Model $model, $subresults) {
        foreach ($model->Behaviors->enabled() as $behaviorName) {
            $subresults = $model->Behaviors->{$behaviorName}->afterFind(
                    $model, $subresults, false
            );
        }

        $subresults = $model->afterFind($subresults, false);

        return $subresults;
    }

    public function beforeSaveAll(\Model $model, $options) {
        $this->_setParentId($model);
        $this->_saveAllAssociationsToRemove($model);
        return true;
    }

    public function afterSaveAll(\Model $model, $created) {
        $this->_removeNotAddedAssociationRows($model);
        return true;
    }

    private function _saveAllAssociationsToRemove(\Model $model) {
        foreach ($this->_associations($model) as $association) {
            foreach ($this->_savedAssociations($model, $association) as $subId) {
                if (!$this->_associationRowOnData($model, $association, $subId)) {
                    $this->toRemoveAssociationIds[$model->name][$association][] = $subId;
                }
            }
        }
    }

    private function _removeNotAddedAssociationRows(\Model $model) {
        foreach ($this->_associations($model) as $association) {
            if (!empty($this->toRemoveAssociationIds[$model->name][$association])) {
                foreach ($this->toRemoveAssociationIds[$model->name][$association] as $subRowId) {
                    $model->{$association}->delete($subRowId);
                }
            }
        }
    }

    private function _associationRowOnData($model, $association, $subrowId) {

        foreach ($this->_dataAssociationRows($model, $association) as $subRow) {
            if (!empty($subRow[$model->{$association}->primaryKey]) && ($subRow[$model->{$association}->primaryKey] == $subrowId)) {
                return true;
            }
        }

        return false;
    }

    private function _associations(\Model $model) {
        return empty($this->options[$model->name][self::SETTING_ASSOCIATIONS]) ? array() : ArrayUtil::arraylize($this->options[$model->name][self::SETTING_ASSOCIATIONS]);
    }

    private function _dataAssociationRows(\Model $model, $association) {
        if (!empty($model->data[$association]) && is_array($model->data[$association])) {
            return $model->data[$association];
        } else {
            return array();
        }
    }

    private function _savedAssociations(\Model $model, $association) {
        if (empty($model->data[$model->alias][$model->primaryKey])) {
            return array();
        }

        $rows = $model->{$association}->find(
                'all', array(
            'conditions' => array(
                $this->_foreignKey($model, $association) => $model->data[$model->alias][$model->primaryKey]
            ),
            'fields' => array(
                "{$model->{$association}->alias}.{$model->{$association}->primaryKey}"
            )
                )
        );

        $ids = array();

        foreach ($rows as $row) {
            $ids[] = $row[$model->{$association}->alias][$model->{$association}->primaryKey];
        }

        return $ids;
    }

    private function _foreignKey(\Model $model, $association) {
        foreach (array($model->hasMany, $model->hasAndBelongsToMany) as $collection) {
            foreach ($collection as $alias => $associationData) {
                if ($alias == $association) {
                    return $alias . '.' . $associationData['foreignKey'];
                }
            }
        }

        return false;
    }

    private function _setParentId(\Model $model) {
        if (empty($model->data[$model->alias][$model->primaryKey])) {
            $model->data[$model->alias][$model->primaryKey] = $this->_newId($model);
        }
    }

    private function _newId(\Model $model) {
        $row = $model->find('first', array('fields' => "max({$model->alias}.{$model->primaryKey}) as maxId"));
        $maxId = empty($row[0]['maxId']) ? 0 : $row[0]['maxId'];
        return $maxId + 1;
    }

}
