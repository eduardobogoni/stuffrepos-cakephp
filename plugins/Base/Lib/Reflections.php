<?php

class Reflections {

    public static function constantsListValues($className, $constantsPrefix) {
        $clazz = new ReflectionClass($className);
        $values = array();
        foreach ($clazz->getConstants() as $name => $value) {
            if (strpos($name, $constantsPrefix) === 0) {
                $values[] = $value;
            }
        }
        return $values;
    }

}
