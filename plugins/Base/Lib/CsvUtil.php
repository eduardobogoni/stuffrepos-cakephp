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

}
