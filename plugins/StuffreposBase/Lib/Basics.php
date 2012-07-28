<?php

App::import('Lib', 'StuffreposBase.ArrayUtil');

class Basics {

    /**
     *
     * @param array $data
     * @param string $field
     * @param string $defaultModel 
     * @return mixed
     */
    public static function fieldValue($data, $field, $defaultModel) {
        return ArrayUtil::arrayIndex($data, self::fieldPath($field, $defaultModel));
    }

    /**
     *
     * @param string $field
     * @param string $defaultModel
     * @return array 
     */
    public static function fieldPath($field, $defaultModel) {
        $fieldPath = explode('.', $field);

        if (count($fieldPath) == 1 && $defaultModel) {
            $fieldPath = array_merge(array($defaultModel), $fieldPath);
        }
        return $fieldPath;
    }

    /**
     *
     * @param string $field
     * @param string $defaultModel
     * @return string
     */
    public static function fieldFullName($field, $defaultModel) {
        return implode('.', self::fieldPath($field, $defaultModel));
    }

}

?>
