<?php

class ModelTraverser {

    const CACHE_KEY = '_modelTraverserCache';
    const FIND_LAST_INSTANCE = 'LastInstance';
    const FIND_ALL = 'All';

    public static function schema(Model $model, $path) {
        try {
            if (!is_array($path)) {
                $path = explode('.', $path);
            }

            if (self::isField($model, $path[0])) {
                if (count($path) == 1) {
                    return self::fieldSchema($model, $path[0]);
                } else {
                    throw new Exception("Path continues, but reached a field.");
                }
            } else if (self::isBelongsToAssociation($model, $path[0]) ||
                    self::isHasOneAssociation($model, $path[0])) {
                return self::schema($model->{$path[0]}, self::pathPopFirst($path));
            } else {
                throw new Exception("Term is not a field or association.");
            }
        } catch (Exception $ex) {
            throw new ModelTraverserException($ex->getMessage(), $model, null, $path, $ex);
        }
    }

    /**
     *
     * @param Model $model
     * @param array $row
     * @param string|array $path
     * @return type
     */
    public static function value(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return $result['all'];
    }

    public static function displayValue(Model $model, $row, $path) {
        $originalPath = $path;
        $result = self::find($model, $row, $path, $row);
        if (!is_array($path)) {
            $path = explode('.', $path);
        }
        end($path);
        $pathLastPart = current($path);
        $association = self::oneToManyAssociationByForeignKey($result['model'], $pathLastPart);
        if ($association) {
            if ($result['all']) {
                return self::value(
                    $result['model']
                    , $result['lastInstance']
                    , array($association, $result['model']->{$association}->displayField)
                );
            } else {
                return null;
            }
        } else {
            return $result['all'];
        }
    }

    public static function lastInstance(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return $result['lastInstance'];
    }

    public static function lastInstancePrimaryKeyValue(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return isset($result['lastInstance'][$result['model']->alias][$result['model']->primaryKey]) ?
            $result['lastInstance'][$result['model']->alias][$result['model']->primaryKey] :
            null;
    }

    public static function lastInstanceAssociationDisplayFieldValue(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);

        $associationAlias = self::oneToManyAssociationByForeignKey(
                        $model, $path[count($path) - 1]
        );

        if (!$associationAlias) {
            throw new ModelTraverserException("Association not found for foreingKey: \"{$path[count($path) - 1]}\"", $model, $row, $path);
        }

        $associationInstance = self::oneToManyAssociationInstanceByPrimaryKey(
                        $model
                        , $associationAlias
                        , $result['all']
        );

        return $associationInstance[$model->{$associationAlias}->alias][$model->{$associationAlias
                }
                ->displayField];
    }

    private static function oneToManyAssociationInstanceByPrimaryKey(
    $model
    , $associationAlias
    , $primaryKeyValue
    ) {
        return $model->{$associationAlias}->find(
                        'first', array(
                    'conditions' => array(
                        "$associationAlias.{$model->{$associationAlias}->primaryKey}" => $primaryKeyValue
                    )
                        )
        );
    }

    private static function oneToManyAssociationByForeignKey(Model $model, $foreignKey) {
        foreach ($model->getAssociated() as $associationAlias => $type) {
            switch ($type) {
                case 'belongsTo':
                case 'hasOne':
                    if ($model->{$type}[$associationAlias]['foreignKey'] == $foreignKey) {
                        return $associationAlias;
                    }
            }
        }

        return false;
    }

    private static function find(Model $model, &$row, $path, &$lastInstance = null) {
        try {
            if (!is_array($path)) {
                $path = explode('.', $path);
            }

            if (count($path) == 0) {
                throw new Exception("Path size is zero.");
            }

            if (self::isField($model, $path[0])) {
                if (count($path) == 1) {
                    return array(
                        'all' => self::_findFieldValue($model, $row, $path[0]),
                        'lastInstance' => $lastInstance,
                        'model' => $model,
                    );
                } else if ($model) {
                    throw new Exception("Path continues, but next model is null. Path: " . print_r($path, true));
                }
            } else if (self::isBelongsToAssociation($model, $path[0]) ||
                    self::isHasOneAssociation($model, $path[0])) {
                if (!isset($row[self::CACHE_KEY][$path[0]])) {
                    if (self::isBelongsToAssociation($model, $path[0])) {
                        $row[self::CACHE_KEY][$path[0]] = self::findBelongsToInstance($model, $path[0], $row);
                    }
                    //self::isHasOneAssociation($model, $path[0])
                    else {
                        $row[self::CACHE_KEY][$path[0]] = self::findHasOneInstance($model, $path[0], $row);
                    }
                }

                if (count($path) == 1) {
                    return array(
                        'all' => $row[self::CACHE_KEY][$path[0]],
                        'lastInstance' => $row[self::CACHE_KEY][$path[0]],
                        'model' => $model->{$path[0]},
                    );
                } else {
                    return self::find(
                                    $model->{$path[0]}
                                    , $row[self::CACHE_KEY][$path[0]]
                                    , self::pathPopFirst($path)
                                    , $row[self::CACHE_KEY][$path[0]]
                    );
                }
            } else if (self::isHasManyAssociation($model, $path[0])) {
                if (!isset($row->{$path[0]})) {
                    $row[self::CACHE_KEY][$path[0]] = self::findHasManyInstance($model, $path[0], $row);
                    if (count($path) == 1) {
                        return array(
                            'all' => $row[self::CACHE_KEY][$path[0]],
                            'lastInstance' => $row[self::CACHE_KEY][$path[0]],
                            'model' => $model->{$path[0]},
                        );
                    } else if ($model) {
                        throw new Exception("Path continues, but reached hasMany association.");
                    }
                }
            } else {
                throw new Exception("Term is not a field or association.");
            }
        } catch (Exception $ex) {
            throw new ModelTraverserException($ex->getMessage(), $model, $row, $path, $ex);
        }
    }

    private static function _findFieldValue(Model $model, $row, $field) {
        if (array_key_exists($field, $row[$model->alias])) {            
            return $row[$model->alias][$field];        
        }
        else if (empty($row[$model->alias][$model->primaryKey])) {
            return null;                   
        }
        else {
            $findRow = $model->find('first', array(
                'recursive' => -1,
                'conditions' => array(
                    $model->alias . '.' . $model->primaryKey => $row[$model->alias][$model->primaryKey],
                ),
            ));
            return empty($findRow) ? null : $row[$model->alias][$field];
        }
    }

    private static function fieldSchema(Model $model, $name) {
        if (($fieldSchema = $model->schema($name))) {
            return $fieldSchema;
        } else if (!empty($model->virtualFields[$name])) {
            if (!empty($model->virtualFieldsSchema[$name])) {
                return $model->virtualFieldsSchema[$name];
            } else {
                return array(
                    'type' => 'string'
                );
            }
        } else {
            throw new Exception("Field not found: {$model->name}.{$name}.");
        }
    }

    private static function isField(Model $model, $name) {
        if (!is_array($schema = $model->schema())) {
            throw new Exception("{$model->name}->schema() do not returned a array. Returned: \"$schema\"" );
        }
        return in_array($name, array_keys($model->schema())) ||
                !empty($model->virtualFields[$name]);
    }

    private static function isBelongsToAssociation(Model $model, $alias) {
        return in_array($alias, $model->getAssociated('belongsTo'));
    }
    
    /**
     * Verifica se $alias é uma associação "hasOne" de $model.
     * @param Model $model
     * @param string $alias
     * @return boolean
     */
    private static function isHasOneAssociation(Model $model, $alias) {
        return in_array($alias, $model->getAssociated('hasOne'));
    }

    private static function findBelongsToInstance(Model $model, $alias, $row) {
        return $model->{$alias}->find(
                        'first', array(
                    'conditions' => array(
                        "{$alias}.{$model->{$alias}->primaryKey}" => $row[$model->alias][$model->belongsTo[$alias]['foreignKey']]
                    ), 'recursive' => -1
                        )
        );
    }

    /**
     * Recupera a instância da associação "hasOne" de $model com alias $alias
     * associada com $row.
     * @param Model $model
     * @param string $alias
     * @param array $row
     * @return array
     */
    private static function findHasOneInstance(Model $model, $alias, $row) {
        return $model->{$alias}->find(
                        'first', array(
                    'conditions' => array(
                        "{$alias}.{$model->hasOne[$alias]['foreignKey']}" => $row[$model->alias][$model->primaryKey]
                    ), 'recursive' => -1
                        )
        );
    }

    private static function isHasManyAssociation(Model $model, $alias) {
        return in_array($alias, $model->getAssociated('hasMany'));
    }

    private static function findHasManyInstance(Model $model, $alias, $row) {
        return $model->{$alias}->find(
                        'all'
                        , array(
                    'conditions' => array(
                        "{$alias}.{$model->hasMany[$alias]['foreignKey']}" => $row[$model->alias][$model->primaryKey]
                    )
                    , 'recursive' => 0
                        )
        );
    }

    private static function pathPopFirst($path) {
        $newPath = array();

        for ($i = 1; $i < count($path); ++$i) {
            $newPath[] = $path[$i];
        }

        return $newPath;
    }

}

class ModelTraverserException extends Exception {

    private $path;
    private $row;
    private $model;

    public function __construct($message, Model $model, $row, $path, $previous = null) {
        $this->path = $path;
        $this->row = $row;
        $this->model = $model;

        parent::__construct(
                $message . ' || ' . print_r(
                        array(
                    'modelName' => $this->model->name,
                    'modelAlias' => $this->model->alias,
                    'path' => $this->path,
                    'row' => $this->row,
                        )
                        , true
                )
                , 1
                , $previous
        );
    }

}
