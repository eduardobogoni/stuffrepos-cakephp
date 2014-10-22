<?php

class CsvUtil {

    private static $defaultOptions = array(
        'delimiter' => ',',
        'enclosure' => '"',
        'encoding' => false,
    );

    public static function fileToArray($filePath, $options = array()) {
        return self::contentToArray(file_get_contents($filePath), $options);
    }

    public static function fileToArrayWithColumns($filePath,
            $convertFunction = null) {
        $lines = self::fileToArray($filePath);
        $columns = array_shift($lines);
        if (!$columns) {
            throw new Exception("File \"$filePath\" has no columns line");
        }
        $ret = array();
        foreach ($lines as $row) {
            $ret[] = self::_rawRowToAssociativeRow($row, $columns, $convertFunction);
        }
        return $ret;
    }

    public static function contentToArray($content, $options = array()) {
        assert(is_array($options), '$options is not a array: ' . var_export($options, true));
        $options += self::$defaultOptions;
        if ($options['encoding']) {
            $content = mb_convert_encoding($content, mb_internal_encoding(), $options['encoding']);
        }
        $lines = array();
        foreach (preg_split ('/\R/', $content) as $k => $sourceLine) {
            if (trim($sourceLine) == '') {
                continue;
            }
            $lines[] = str_getcsv($sourceLine, $options['delimiter'], $options['enclosure']);
        }
        return $lines;
    }

    public static function arrayToFile($filePath, $lines) {
        if (($handle = fopen($filePath, "w")) !== FALSE) {
            foreach ($lines as $line) {
                fputcsv($handle, $line);
            }
            fclose($handle);
        } else {
            throw new Exception("Failed to open file \"$filePath\"");
        }
    }

    private static function _rawRowToAssociativeRow($row, $columns,
            $convertFunction) {
        $ret = array();
        foreach ($columns as $columnIndex => $columnName) {
            $ret[$columnName] = self::_rowValue($row[$columnIndex], $columnName, $convertFunction);
        }
        return $ret;
    }

    private static function _rowValue($value, $column, $convertFunction) {
        return $convertFunction ?
                call_user_func($convertFunction, $column, $value) :
                $value;
    }

}
