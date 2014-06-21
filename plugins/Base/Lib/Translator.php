<?php

class Translator {

    public static function termsToTranslate() {
        $terms = array();
        foreach (self::_findModels() as $model) {
            $terms[$model->plugin] = empty($terms[$model->plugin]) ?
                    self::_modelTerms($model) :
                    array_merge($terms[$model->plugin], self::_modelTerms($model));
        }
        $ret = array();
        foreach ($terms as $plugin => $pluginTerms) {
            $ret[$plugin] = self::_uniqueTerms($pluginTerms);
        }
        ksort($ret);
        return $ret;
    }

    private static function _uniqueTerms($terms) {
        $ret = array_unique($terms);
        sort($ret);
        return $ret;
    }

    /**
     * 
     * @param \Model $model
     * @return string[]
     */
    private static function _modelTerms(\Model $model) {
        $terms = array(
            $model->name,
            preg_replace('/([a-z])([A-Z])/', '\1 \2', $model->name),
            Inflector::pluralize($model->name),
            Inflector::pluralize(
                    preg_replace('/([a-z])([A-Z])/', '\1 \2', $model->name)),
        );
        if (is_array($model->schema())) {
            foreach (array_keys($model->schema()) as $field) {
                $terms[] = Inflector::humanize(preg_replace('/_id$/', '', $field));
            }
        }
        foreach (array_keys($model->virtualFields) as $field) {
            $terms[] = Inflector::humanize(preg_replace('/_id$/', '', $field));
        }
        if (method_exists($model, 'toTranslation')) {
            $terms = array_merge($terms, $model->toTranslation());
        }
        return $terms;
    }

    /**
     * 
     * @return \Model[]
     */
    private static function _findModels() {
        $models = array();
        foreach (App::objects('model') as $modelName) {
            if ($modelName != 'AppModel') {
                App::import('Model', $modelName);

                $class = new ReflectionClass($modelName);
                if (!$class->isAbstract()) {
                    $models[] = $class->newInstance();
                }
            }
        }
        return $models;
    }

}
