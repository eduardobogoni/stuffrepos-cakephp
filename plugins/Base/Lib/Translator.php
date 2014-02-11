<?php

class Translator {

    public static function termsToTranslate() {
        $terms = array();

        foreach (self::_findModels() as $model) {
            $terms[] = $model->name;
            $terms[] = preg_replace('/([a-z])([A-Z])/', '\1 \2', $model->name);
            $terms[] = Inflector::pluralize($model->name);
            $terms[] = Inflector::pluralize(
                            preg_replace('/([a-z])([A-Z])/', '\1 \2',
                                    $model->name));

            if (is_array($model->schema())) {
                foreach (array_keys($model->schema()) as $field) {
                    $terms[] = Inflector::humanize(preg_replace('/_id$/', '',
                                            $field));
                }
            }

            foreach (array_keys($model->virtualFields) as $field) {
                $terms[] = Inflector::humanize(preg_replace('/_id$/', '', $field));
            }

            if (method_exists($model, 'toTranslation')) {
                $terms = array_merge($terms, $model->toTranslation());
            }
        }

        $notRepeatedTerms = array();

        foreach ($terms as $term) {
            $notRepeatedTerms[$term] = true;
        }

        $notRepeatedTerms = array_keys($notRepeatedTerms);
        sort($notRepeatedTerms);
        return $notRepeatedTerms;
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
