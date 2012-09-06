<?php

class ArrayUtil {

    public static function hasArrayIndex($array, $index) {
        return self::arrayIndex($array, $index) !== null;
    }

    public static function arrayIndex($array, $index) {
        foreach ($index as $i) {
            if (isset($array[$i])) {
                $array = &$array[$i];
            } else {
                return null;
            }
        }

        return $array;
    }

    public static function keysAsValues($array) {
        $newArray = array();
        foreach ($array as $value) {
            $newArray[$value] = $value;
        }
        return $newArray;
    }

    /**
     * 
     * @param mixed $var
     * @return array
     */
    public static function arraylize($var) {
        if (is_array($var)) {
            return $var;
        }

        if ($var === false || $var === null) {
            return array();
        }

        return array($var);
    }

    /**
     *
     * @param array $array
     * @return array 
     */
    public static function array2NamedParams($array) {
        $params = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach (array2NamedParams($value) as $subKey => $subValue) {
                    $params[$key . '.' . $subKey] = $subValue;
                }
            } else {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    public static function mergeArrayWithKeys($array1, $array2) {
        foreach ($array2 as $key => $value) {
            $array1[$key] = $value;
        }

        return $array1;
    }

    /**
     *
     * @param array $array
     * @param array $index
     * @param mixed $value 
     */
    public static function setByArray(&$array, $index, $value) {
        $ref = &$array;
        foreach ($index as $i) {
            $ref = &$ref[$i];
        }

        $ref = $value;
    }

}

?>
