<?php

App::import('Lib', 'Base.ArrayUtil');

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

    /**
     * Converte um nome de campo (Identificadores separados por ponto) para um 
     * array
     * @param string $fieldName 
     */
    public static function fieldNameToArray($fieldName) {
        return explode('.', $fieldName);
    }

    public static function lcd($n, $m, $maxvarianzpercent = 0) {
        // set $maxvarianzpercent=5 to get a small, but approx. result
        /* a better lcd function with varianz:
          for example use
          lcd(141,180,5) to get the approx. lcd '7/9' which is in fact 140/180
         */
        // ATTENTION!!! can be really slow if $m is >1000

        $d = $n / $m;
        $f = 1;
        while ($d * $f != intval($d * $f)) {
            $f++;
        }
        $r = ($d * $f) . '/' . $f;
        if (($d * $f) <= 10 or $f <= 10)
            return $r;
        else if ($maxvarianzpercent > 0) {
            $f = 1;
            while ($d * $f != intval($d * $f) and ($d * $f) - intval($d * $f) > $maxvarianzpercent / 100) {
                $f++;
            }
            return intval($d * $f) . '/' . $f;
        } else
            return $r;
    }

    public static function multibyteTrim($string) {
        $string = preg_replace("/(^\s+)|(\s+$)/us", "", $string);

        return $string;
    }

}

?>