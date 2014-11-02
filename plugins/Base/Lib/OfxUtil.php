<?php

class OfxUtil {

    public static function fileToArray($path) {
        return XmlUtil::xmlToArray(self::fileToXml($path));
    }
    
    /**
     * 
     * @param string $path Path to the OFX file.
     * @return \SimpleXMLElement
     * @throws Exception
     */
    public static function fileToXml($path) {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new Exception("Could not read file \"$path\"");
        }
        $data = '';
        while (!feof($handle)) {
            $line = trim(fgets($handle));
            if ($line == '' || preg_match('/^.+\:.+$/', $line)) {
                continue;
            } else {
                $data = $line . PHP_EOL;
                break;
            }
        }
        while (!feof($handle)) {
            $data .= fread($handle, 4096);
        }
        $data = str_replace('&', '&amp;', $data);
        return new SimpleXMLElement($data);        
    }

}
