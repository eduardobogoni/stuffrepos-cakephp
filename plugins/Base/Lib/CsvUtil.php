<?php

class CsvUtil {

    public static function fileToArray($filePath) {
        $lines = array();
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $lines[] = $data;                
            }
            fclose($handle);
        }
        return $lines;
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

    public static function contentToArray($content, $delimiter = ',',
            $enclosure = '"') {
        $lines = array();
        foreach (preg_split ('/\R/', $content) as $k => $sourceLine) {
            if (trim($sourceLine) == '') {
                continue;
            }
            $lines[] = str_getcsv($sourceLine, $delimiter, $enclosure);
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
