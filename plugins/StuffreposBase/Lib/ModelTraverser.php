<?php

class ModelTraverser {

    const CACHE_KEY = '_modelTraverserCache';
    const FIND_LAST_INSTANCE = 'LastInstance';
    const FIND_ALL = 'All';

    public function schema(Model $model, $path) {
        try {
            if (!is_array($path)) {
                $path = explode('.', $path);
            }

            if (self::isField($model, $path[0])) {
                if (count($path) == 1) {
                    return $model->schema($path[0]);
                } else {
                    throw new Exception("Path continues, but reached a field. Path: " . print_r($path, true));
                }
            } else if (self::isBelongsToAssociation($model, $path[0])) {
                return self::schema($model->{$path[0]}, self::pathPopFirst($path));
            } else {
                throw new Exception("Term is not a field or association. Path: " . print_r($path, true));
            }
        } catch (Exception $ex) {
            throw new ModelTraverserException($ex->getMessage(), $model, $row, $path, $ex);
        }
    }

    /**
     *
     * @param mixed $path 
     * @return mixed
     */
    public static function value(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return $result['all'];
    }

    public static function lastInstance(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return $result['lastInstance'];
    }

    public static function lastAssociationPrimaryKeyValue(Model $model, $row, $path) {
        $result = self::find($model, $row, $path, $row);
        return $result['lastInstance'][$result['model']->alias][$result['model']->primaryKey];
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
                        'all' => $row[$model->alias][$path[0]],
                        'lastInstance' => $lastInstance,
                        'model' => $model,
                    );
                } else if ($model) {
                    throw new Exception("Path continues, but next model is null. Path: " . print_r($path, true));
                }
            } else if (self::isBelongsToAssociation($model, $path[0])) {
                if (!isset($row->{$path[0]})) {
                    $row[self::CACHE_KEY][$path[0]] = self::findBelongsToInstance($model, $path[0], $row);
                }

                if (count($path) == 1) {
                    return array(
                        'all' => $row[self::CACHE_KEY][$path[0]],
                        'lastInstance' => $row[self::CACHE_KEY][$path[0]],
                        'model' => $model->{$path[0]},
                    );
                }
                else {
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

    private static function isField(Model $model, $name) {
        return in_array($name, array_keys($model->schema()));
    }

    private static function isBelongsToAssociation(Model $model, $alias) {
        return in_array($alias, $model->getAssociated('belongsTo'));
    }

    private static function findBelongsToInstance(Model $model, $alias, $row) {
        return $model->{$alias}->find(
                        'first', array(
                    'conditions' => array(
                        "{$alias}.{$model->{$alias}->primaryKey}" => $row[$model->alias][$model->belongsTo[$alias]['foreignKey']]
                    ), 'recursive' => 0
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