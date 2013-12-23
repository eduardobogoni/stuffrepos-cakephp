<?php

class ControllerInspector {

    public static function actions(\Controller $controller) {
        $parentClassMethods = get_class_methods(get_parent_class($controller));
        $subClassMethods = get_class_methods($controller);
        $actions = array_diff($subClassMethods, $parentClassMethods);
        if ($controller->scaffold !== false) {
            foreach (array('index', 'add', 'view', 'edit', 'delete') as $action) {
                if (!in_array($action, $actions)) {
                    $actions[] = $action;
                }
            }
        }
        return array_filter($actions, function($value) {
            return strpos($value, '_') !== 0;
        });
    }

    public static function actionExists(\Controller $controller, $action) {
        return in_array($action, self::actions($controller));
    }

}
