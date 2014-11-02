<?php

class XmlUtil {

    /**
     * 
     * @param SimpleXMLElement $xmlElement
     * @return array
     */
    public static function xmlToArray(SimpleXMLElement $xmlElement) {
        return array(
            $xmlElement->getName() => self::__xmlToArray($xmlElement)
        );
    }

    private static function __xmlToArray(SimpleXMLElement $xmlElement) {
        $data = array();
        foreach ($xmlElement->attributes() as $key => $value) {
            $data['@' . $key] = $value;
        }
        foreach ($xmlElement->children() as $child) {
            $data[$child->getName()][] = self::__xmlToArray($child);
        }
        $data['__DATA__'] = $xmlElement->__toString();
        return $data;
    }

}
