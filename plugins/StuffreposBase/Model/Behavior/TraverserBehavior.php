<?php

class TraverserBehavior extends ModelBehavior {

    const ASSOCIATIVE_KEY = '@';

    public function beforeFind(Model $model, $query) {
        $parentQuery = parent::beforeFind($model, $query);
        if (is_array($parentQuery)) {
            $query = $parentQuery;
        }
        $query['recursive'] = 0;
        return $query;
    }

    public function afterFind(Model $model, $results, $primary) {
        $parentResults = parent::afterFind($model, $results, $primary);

        if ($parentResults) {
            $results = $parentResults;
        }

        foreach (array_keys($results) as $k) {
            $results[$k][self::ASSOCIATIVE_KEY] = new Traverser_Field(
                            $model, $results[$k][$model->alias]
            );
        }

        return $results;
    }

}

class Traverser_Field {

    /**
     *
     * @var Model
     */
    private $model;

    /**
     *
     * @var array
     */
    private $row;

    public function __construct(Model $model, &$row) {
        $this->model = $model;
        $this->row = $row;
    }

    /**
     *
     * @param mixed $path 
     * @return mixed
     */
    public function value($path) {
        if (!is_array($path)) {
            $path = explode('.', $path);
        }

        if ($this->isField($path[0])) {
            $value = &$this->row[$path[0]];
            $model = null;
        } else if ($this->isBelongsToAssociation($path[0])) {
            if (!isset($this->row[$path[0]])) {
                $this->row[$path[0]] = $this->findBelongsToInstance($path[0]);
            }
            $value = &$this->row[$path[0]];
            $model = $this->model->{$path[0]};
        } else {
            throw new Exception("Term is not a field or association. Path: " . print_r($path, true));
        }

        if (count($path) == 1) {
            return $value;
        } else if ($model) {
            return $value[TraverserBehavior::ASSOCIATIVE_KEY]->value($this->pathPopFirst($path));
        } else {
            throw new Exception("Path continues, but next model is null. Path: " . print_r($path, true));
        }
    }

    private function isField($name) {
        return in_array($name, array_keys($this->model->schema()));
    }

    private function isBelongsToAssociation($alias) {
        return in_array($alias, $this->model->getAssociated('belongsTo'));
    }

    private function findBelongsToInstance($alias) {
        $this->model->{$alias}->Behaviors->attach('Traverser');
        return $this->model->{$alias}->find(
                        'first', array(
                    'conditions' => array(
                        "{$alias}.{$this->model->{$alias}->primaryKey}" => $this->row[$this->model->belongsTo[$alias]['foreignKey']]
                    )
                        )
        );
    }

    private function pathPopFirst($path) {
        $newPath = array();

        for ($i = 1; $i < count($path); ++$i) {
            $newPath[] = $path[$i];
        }

        return $newPath;
    }

}

?>
