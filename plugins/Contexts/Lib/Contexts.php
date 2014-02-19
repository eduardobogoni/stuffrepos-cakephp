<?php

App::uses('ClassSearcher', 'Base.Lib');

class Contexts {

    public static $contexts = array();

    /**
     * 
     * @param string $id
     * @return Context
     * @throws Exception
     */
    public static function getContext($id) {
        if (empty(self::$contexts[$id])) {
            self::$contexts[$id] = ClassSearcher::findInstanceAndInstantiate(
                            'Lib/Context', Inflector::camelize($id) . 'Context'
            );
        }
        return self::$contexts[$id];
    }

}
