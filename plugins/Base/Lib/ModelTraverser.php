<?php

class ModelTraverser {

    const CACHE_KEY = '_modelTraverserCache';
    const FIND_LAST_INSTANCE = 'LastInstance';
    const FIND_ALL = 'All';
    const NODE_TYPE_SELF = 'self';
    const NODE_TYPE_FIELD = 'field';
    const NODE_TYPE_HAS_ONE = 'has_one';
    const NODE_TYPE_HAS_MANY = 'has_many';

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
            throw new ModelTraverserException($ex->getMessage(), compact('model','path'), $ex);
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
        $stack = self::find($model, $row, $path);
        $top = self::_pathLastNode($stack);
        return $top['value'];
    }

    public static function displayValue(Model $model, $row, $path) {
        $stack = self::find($model, $row, $path);
        $top = self::_pathLastNode($stack);
        $pathLastPart = self::_pathLastNode($path);
        $association = self::oneToManyAssociationByForeignKey($top['model'], $pathLastPart);
        if ($association) {            
            array_pop($stack);
            if (empty($stack)) {
                $subTop = array(
                    'model' => $model,
                    'value' => $row,
                );
            }
            else {
                $subTop = self::_pathLastNode($stack);
            }
            $subPath = array($association, $subTop['model']->{$association}->displayField);
            return self::value(
                            $subTop['model']
                            , $subTop['value']
                            , $subPath
            );
        } else {
            return $top['value'];
        }
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

    public static function find(Model $model, $row, $path) {
        try {
            if (!is_array($path)) {
                $path = explode('.', $path);
            }

            if (count($path) == 0) {
                throw new Exception("Path size is zero.");
            }
            if (!is_array($row)) {
                throw new Exception('$row is not a array');
            }
            $currentModel = $model;
            $leftPath = $path;
            $stack = array();
            while (!empty($leftPath)) {
                $currentNode = $leftPath[0];
                $leftPath = self::pathPopFirst($leftPath);
                $currentNodeType = self::_findCurrentNodeType($currentModel, $currentNode);
                switch ($currentNodeType) {
                    case self::NODE_TYPE_FIELD:
                        $currentRow = self::_findField($currentModel, $row, $currentNode);
                        break;

                    case self::NODE_TYPE_SELF:
                        $currentRow = self::_findSelf($currentModel, $row, $currentNode);
                        break;

                    case self::NODE_TYPE_HAS_ONE:
                        $currentRow = self::_findHasOne($currentModel, $row, $currentNode);
                        $currentModel = $currentModel->{$currentNode};
                        break;

                    case self::NODE_TYPE_HAS_MANY:
                        $currentRow = self::_findHasMany($currentModel, $row, $currentNode);
                        $currentModel = $currentModel->{$currentNode};
                        break;

                    default:
                        $currentRow = new Exception("Current node type \"$currentNodeType\" not mapped");
                        break;
                }
                $stack[] = array(
                    'value' => $currentRow,
                    'model' => $currentModel,
                );
            }
            return $stack;
        } catch (Exception $ex) {
            throw new ModelTraverserException($ex->getMessage(), compact('model', 'row', 'path', 'leftPath', 'stack', 'currentModel', 'currentRow', 'currentNode', 'currentNodeType'), $ex);
        }
    }

    private static function _findCurrentNodeType(\Model $model, $currentNode) {
        if (self::isField($model, $currentNode)) {
            return self::NODE_TYPE_FIELD;
        } else if ($model->alias == $currentNode) {
            return self::NODE_TYPE_SELF;
        } else if (self::isBelongsToAssociation($model, $currentNode) ||
                self::isHasOneAssociation($model, $currentNode)) {
            return self::NODE_TYPE_HAS_ONE;
        } else if (self::isHasManyAssociation($model, $currentNode)) {
            return self::NODE_TYPE_HAS_MANY;
        } else {
            throw new Exception("Node \"{$currentNode}\" is not a field or association of \"{$model->name}\" (Alias: \"{$model->alias}\").");
        }
    }

    public static function _findField(Model $model, $row, $field, $required = true) {
        if (array_key_exists($model->alias, $row) && array_key_exists($field, $row[$model->alias])) {
            return $row[$model->alias][$field];
        } else if (array_key_exists($field, $row)) {
            return $row[$field];
        } else if (empty($row[$model->alias][$model->primaryKey])) {
            return null;
        } else {
            $findRow = $model->find('first', array(
                'recursive' => -1,
                'conditions' => array(
                    $model->alias . '.' . $model->primaryKey => $row[$model->alias][$model->primaryKey],
                ),
            ));
            if (empty($findRow)) {
                if ($required) {
                    throw new Exception("Row not found");
                }
                else {
                    return null;
                }
            }
            if (array_key_exists($model->alias, $findRow) && array_key_exists($field, $findRow[$model->alias])) {
                return $findRow[$model->alias][$field];
            } else if ($required) {
                throw new Exception("Field \"$field\" not found for \"{$model->name}\" record: " . print_r($findRow, true));
            } else {
                return null;            
            }
        }
    }

    public static function _findSelf(Model $model, $row) {
        return array_key_exists($model->alias, $row) ?
                $row[$model->alias] :
                $row;
    }

    public static function _findHasOne(Model $model, &$row, $alias) {
        if (!array_key_exists($alias, $row)) {
            if (self::isBelongsToAssociation($model, $alias)) {
                $row[$alias] = self::findBelongsToInstance($model, $row, $alias);
            }
            //self::isHasOneAssociation($model, $alias)
            else {
                $row[$alias] = self::findHasOneInstance($model, $alias, $row);
            }
        }
        return $row[$alias];
    }

    public static function _findHasMany(Model $model, &$row, $alias) {
        if (!array_key_exists($alias, $row)) {
            $row[$alias] = self::findHasManyInstance($model, $row, $alias);
        }
        return $row[$alias];
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

    private static function findBelongsToInstance(Model $model, $row, $alias) {
        $ret = $model->{$alias}->find(
                'first', array(
            'conditions' => array(
                "{$alias}.{$model->{$alias}->primaryKey}" => self::_findField($model, $row, $model->belongsTo[$alias]['foreignKey']),
            ), 'recursive' => -1
                )
        );

        if (array_key_exists($alias, $ret)) {
            return $ret[$alias];
        } else {
            return array();
        }
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

    private static function findHasManyInstance(Model $model, $row, $alias) {
        if (!array_key_exists($alias, $model->hasMany)) {
            throw new Exception("Model \"{$model->name}\" has no hasMany association \"$alias\"");
        }
        $records = $model->{$alias}->find(
                'all'
                , array(
            'conditions' => array(
                "{$alias}.{$model->hasMany[$alias]['foreignKey']}" => $row[$model->alias][$model->primaryKey]
            )
            , 'recursive' => -1
                )
        );
        $ret = array();
        foreach ($records as $record) {
            $ret[] = $record[$alias];
        }
        return $ret;
    }

    private static function pathPopFirst($path) {
        $newPath = array();

        for ($i = 1; $i < count($path); ++$i) {
            $newPath[] = $path[$i];
        }

        return $newPath;
    }

    /**
     * 
     * @param mixed $path
     * @return mixed
     */
    private static function _pathLastNode($path) {
        $arrayPath = is_array($path) ?
                $path :
                explode('.', $path);
        if (empty($arrayPath)) {
            throw new ModelTraverserException("Path is empty", compact('path'));
        }
        end($arrayPath);
        return current($arrayPath);
    }

}

class ModelTraverserException extends Exception {

    /**
     * 
     * @param string $message
     * @param Model $model
     * @param array $params
     * @param Exception $previous
     */
    public function __construct($message, $params, $previous = null) {
        parent::__construct(
                $message . ' || ' . print_r(
                        self::_buildDebug($params)
                        , true
                )
                , 1
                , $previous
        );
    }

    public static function debug($var) {
        debug(self::_buildDebug($var));
    }

    private static function _buildDebug($var) {
        if ($var instanceof Model) {
            return get_class($var) . " ({$var->name}/{$var->alias})";
        } else if (is_object($var)) {
            return 'Object ' . get_class($var);
        } else if (is_array($var)) {
            $ret = array();
            foreach ($var as $key => $value) {
                $ret[$key] = self::_buildDebug($value);
            }
            return $ret;
        } else {
            return $var;
        }
    }

}
