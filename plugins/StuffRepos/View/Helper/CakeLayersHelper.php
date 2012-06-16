<?php

class CakeLayersHelper extends Helper {

    public function getController($controllerName = null) {
        if (empty($controllerName)) {
            $controllerName = $this->params['controller'];
        }
        $controllerClassUnderscore = $controllerName . '_controller';
        $controller = ClassRegistry::getObject($controllerClassUnderscore);
        if (!$controller) {
            App::import('Controller', $controllerName);
            $controllerClass = Inflector::camelize($controllerName . '_controller');
            $controller = new $controllerClass;
            $controller->constructClasses();
            if (!empty($controller->SubmoduleOneToMany)) {
                $settings = array();
                if (!empty($controller->components['SubmoduleOneToMany'])) {
                    $settings = $controller->components['SubmoduleOneToMany'];
                }
                $controller->SubmoduleOneToMany->initialize(
                        $controller, $settings);
                $controller->SubmoduleOneToMany->startup($controller);
            }
            ClassRegistry::getInstance()->addObject($controllerClassUnderscore, &$controller);
        }
        return $controller;
    }

    public function getControllerDefaultModelClass($controllerName = null) {
        return $this->getController($controllerName)->modelClass;
    }

    public function getControllerDefaultModel($controllerName= null) {
        return $this->getModel($this->getControllerDefaultModelClass($controllerName));
    }

    public function getModel($model, $required = false) {
        if ($model instanceof Model) {
            $modelName = $model->name;
        } elseif (is_string($model)) {
            $modelName = $model;
            $model = ClassRegistry::getObject($modelName);
            if (!$model) {
                $model = & ClassRegistry::init($modelName);
            }
        } else {
            $modelName = $model . ' [' . gettype($model) . ']';
            $model = null;
        }

        if (!$model && $required) {
            throw new Exception("Model not found: \"$modelName\".");
        }

        if ($model instanceof Model) {
            $model->recursive = 0;
        }

        return $model;
    }

    private function getModelName($model) {
        return $model instanceof Model ? $model->name : $model;
    }

    public function getFieldSchema($fieldName, $modelClass) {
        $modelSchema = $this->getModel($modelClass)->schema();
        if (!empty($modelSchema[$fieldName])) {
            return $modelSchema[$fieldName];
        }

        if (!empty($this->getModel($modelClass)->virtualFieldsSchema)) {
            $virtualModelSchema = $this->getModel($modelClass)->virtualFieldsSchema;
            if (!empty($virtualModelSchema[$fieldName])) {
                return $virtualModelSchema[$fieldName];
            }
        }

        return false;
    }

    public function getModelAssociations($model) {
        $modelParam = $model;
        if ($model) {
            if (is_string($model)) {
                $model = $this->getModel($model);
            }
        } else {
            $model = $this->getControllerDefaultModel(null);
        }

        if (!$model instanceof Model) {
            throw new Exception("not a model " . print_r($modelParam, true));
        }
        $keys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
        $associations = array();

        foreach ($keys as $key => $type) {
            foreach ($model->{$type} as $assocKey => $assocData) {
                $associations[$type][$assocKey]['alias'] = $assocKey;
                $associations[$type][$assocKey]['type'] = $type;
                $associations[$type][$assocKey]['className'] = $assocData['className'];

                $associations[$type][$assocKey]['primaryKey'] =
                        $model->{$assocKey}->primaryKey;

                $associations[$type][$assocKey]['displayField'] =
                        $model->{$assocKey}->displayField;

                $associations[$type][$assocKey]['foreignKey'] =
                        $assocData['foreignKey'];
                
                $associations[$type][$assocKey]['order'] =
                        (empty($assocData['order']) ? false : $assocData['order']);

                $associations[$type][$assocKey]['controller'] =
                        Inflector::pluralize(Inflector::underscore($assocData['className']));

                if ($type == 'hasAndBelongsToMany') {
                    $associations[$type][$assocKey]['with'] = $assocData['with'];
                }
            }
        }
        return $associations;
    }

    public function getCurrentView() {
        return ClassRegistry::getObject('view');
    }

    public function modelInstanceField($model, $instance, $field, $toDisplay = false) {
        if (is_string($field)) {
            $field = explode('.', $field);
        }

        if (count($field) == 1) {
            $field = array_merge(
                    array($model), $field
            );
        }

        return $this->modelInstanceFieldByPath($model, $instance, $field, $toDisplay);
    }

    public function modelInstanceFieldByPath($model, $instance, $path, $toDisplay = false) {
        try {
            $modelName = $model instanceof Model ? $model->name : $model;
            $model = $this->getModel($model);

            if (isset($instance[$path[0]])) {
                if ($toDisplay && $model && ($association = $this->_associationByForeingKey($model, $path[0]))) {
                    return $this->modelInstanceFieldByPath(
                                    $model, $instance, array(
                                $association['alias'],
                                $this->getModel($association['className'])->displayField,
                                $toDisplay
                                    )
                    );
                }
                $value = $instance[$path[0]];
                if (is_array($value) && $model) {
                    return $this->modelInstanceFieldByPath(
                                    $this->modelAssociationModel($model, $path[0])
                                    , $value
                                    , $this->_pathPopFirst($path)
                                    , $toDisplay
                    );
                }
                return $value;
            } else {
                $association = $this->modelAssociationOneType($model, $path[0]);

                if ($association) {
                    $associationInstance = $this->associationInstance($model, $association['alias'], $instance);
                    if (isset($associationInstance[$association['alias']])) {
                        return $this->modelInstanceFieldByPath(
                                        $this->modelAssociationModel($model, $association['alias'])
                                        , $associationInstance[$association['alias']]
                                        , $this->_pathPopFirst($path)
                                        , $toDisplay);
                    }
                }

                return null;
            }
        } catch (Exception $ex) {
            throw new Exception(print_r(compact('modelName', 'instance', 'path', 'toDisplay'), true), 0, $ex);
        }
    }

    private function _pathPopFirst($path) {
        $newPath = array();

        for ($i = 1; $i < count($path); ++$i) {
            $newPath[] = $path[$i];
        }

        return $newPath;
    }

    public function modelAssociationModel($model, $associationAlias, $required = false) {
        return $this->modelAssociationModelByPath(
                        $model
                        , explode('.', $associationAlias)
                        , $required
        );
    }

    public function modelAssociationModelByPath($model, $associationPath, $required = false) {
        $model = $this->getModel($model);
        $associationModel = false;
        if ($model->name == $associationPath[0]) {
            $associationModel = $model;
        } else {
            $association = $this->modelAssociation($model, $associationPath[0]);

            if ($association) {
                $associationModel = $this->getModel($association['className'], $required);
            }
        }

        if ($associationModel) {
            if (count($associationPath) > 1) {
                return $this->modelAssociationModelByPath(
                                $associationModel
                                , $this->_pathPopFirst($associationPath)
                                , $required
                );
            } else {
                return $associationModel;
            }
        }

        if ($required) {
            throw new Exception("Model de associação não foi encontrado.");
        } else {
            return null;
        }
    }

    private function modelAssociation($model, $associationAlias) {
        $modelAssociations = $this->getModelAssociations($model);
        foreach ($modelAssociations as $type => $associations) {
            foreach ($associations as $alias => $associationData) {
                if ($alias == $associationAlias) {
                    return $associationData;
                }
            }
        }

        return null;
    }

    public function modelAssociationOneType($model, $associationAlias) {
        $association = $this->modelAssociation($model, $associationAlias);

        if (!empty($association)) {
            switch ($association['type']) {
                case 'belongsTo':
                case 'hasOne':
                    return $association;
            }
        }

        return null;
    }

    private function associationInstance($model, $associationAlias, $instance, $required = false) {
        $association = $this->modelAssociation($model, $associationAlias);

        if (!empty($association)) {
            switch ($association['type']) {
                case 'belongsTo':
                    return $this->belongsToAssociationInstance($model, $association, $instance);

                case 'hasOne':
                    return $this->hasOneAssociationInstance($model, $association, $instance);
            }
        }

        if ($required) {
            $modelName = $this->getModelName($model);
            throw new Exception("Instância de associação não foi encontrada. " . print_r(compact('modelName', 'associationAlias', 'instance'), true));
        }

        return null;
    }

    public function associationInstances($model, $associationAlias, $instance) {
        return $this->associationInstancesByPath(
                        $model
                        , explode('.', $associationAlias)
                        , $instance
        );
    }

    /**
     *
     * @param mixed $model
     * @param array $associationPath
     * @param array $instance 
     */
    public function associationInstancesByPath($model, $associationPath, $instance) {
        $model = $this->getModel($model, true);

        if (count($associationPath) > 1) {
            $associationInstance = $this->associationInstance(
                    $model
                    , $associationPath[0]
                    , $instance
                    , true
            );
            if ($associationInstance) {
                return $this->associationInstancesByPath(
                                $this->modelAssociationModel($model, $associationPath[0])
                                , $this->_pathPopFirst($associationPath)
                                , $associationInstance
                );
            } else {
                return array();
            }
        } else {
            if (isset($instance[$model->alias])) {
                $instance = $instance[$model->alias];
            }
            $associationModel = $this->modelAssociationModel($model, $associationPath[0]);
            $association = $this->modelAssociation($model, $associationPath[0]);
            return $associationModel->find(
                            'all', array(
                        'conditions' => array(
                            "{$associationModel->alias}.{$association['foreignKey']}" => $instance[$model->primaryKey]
                        ),
                        'order' => (empty($association['order']) ? false : $association['order'])
                            )
            );
        }
    }

    private function belongsToAssociationInstance(Model $model, $association, $instance) {
        if (!isset($this->cache['belongsToAssociationInstance'][$model->name][$association['alias']][$instance[$association['foreignKey']]])) {

            $associationModel = $this->getModel($association['className']);
            $model = $this->getModel($model, true);

            if ($associationModel == null) {
                throw new Exception("Association Model is null");
            }

            $this->cache['belongsToAssociationInstance'][$model->name][$association['alias']][$instance[$association['foreignKey']]] = $associationModel->find(
                    'first'
                    , array(
                'conditions' => array(
                    "{$associationModel->alias}.{$associationModel->primaryKey}" => $instance[$association['foreignKey']]
                )
                    )
            );
        }

        return $this->cache['belongsToAssociationInstance'][$model->name][$association['alias']][$instance[$association['foreignKey']]];
    }

    private function hasOneAssociationInstance(Model $model, $association, $instance) {
        if (isset($instance[$model->alias])) {
            $instance = $instance[$model->alias];
        }

        $modelName = $model->name;

        //debug(compact('modelName', 'association', 'instance'));
        if (!isset($this->cache[__METHOD__][$model->name][$association['alias']][$instance[$model->primaryKey]])) {
            $associationModel = $this->getModel($association['className'], true);
            $model = $this->getModel($model, true);

            $this->cache[__METHOD__][$model->name][$association['alias']][$instance[$model->primaryKey]] = $associationModel->find(
                    'first'
                    , array(
                'conditions' => array(
                    "{$associationModel->alias}.{$association['foreignKey']}" => $instance[$model->primaryKey]
                )
                    )
            );
        }

        return $this->cache[__METHOD__][$model->name][$association['alias']][$instance[$model->primaryKey]];
    }

    private function _associationByForeingKey($model, $field) {
        $modelAssociations = $this->getModelAssociations($model);
        foreach ($modelAssociations as $type => $associations) {
            foreach ($associations as $alias => $associationData) {
                if ($associationData['foreignKey'] == $field) {
                    return $associationData;
                }
            }
        }

        return null;
    }

}

?>
