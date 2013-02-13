<?php

class ArrayUtil {

    public static function hasArrayIndex($array, $index) {
        try {
            self::arrayIndex($array, $index, true);            
            return true;
        } catch (OutOfBoundsException $ex) {
            return false;
        }
    }

    /**
     * 
     * @param array $array
     * @param array $index
     * @param boolean $required
     * @return mixed
     */
    public static function arrayIndex($array, $index, $required = false) {
        $current = $array;
        foreach ($index as $i) {
            if (array_key_exists($i, $current)) {
                $current = &$current[$i];
            } else if ($required) {
                throw new OutOfBoundsException("Index not found: " . print_r(compact('array', 'index', 'required', 'current'), true));                
            } else {
                return null;
            }
        }

        return $current;
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