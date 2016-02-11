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
        $skip = true;
        $data = '';
        foreach(self::readLines($path) as $line) {
            $l = trim($line);
            if ($skip && !($l == '' || preg_match('/^.+\:.+$/', $l))) {
                $data = $l . PHP_EOL;
                $skip = false;
            }
            else {
                $data .= $line . PHP_EOL;
            }
        }
        $data = str_replace('&', '&amp;', $data);
        file_put_contents('/tmp/teste.xml', $data);
        return new SimpleXMLElement($data);        
    }

    private static function readLines($path) {
      if (!file_exists($path)) {
          throw new Exception("Could not read file \"$path\"");
      }
      $data = file_get_contents($path);
      $enc = mb_detect_encoding($data);
      return explode("\n", mb_convert_encoding($data, $enc));
    }

}
