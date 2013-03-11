<?php

class OpenLdapUtils {

    public static function hashPassword($password, $function = 'md5') {
        switch ($function) {
            case 'md5':
                return '{MD5}' . base64_encode(pack('H*', md5($password)));
                
            default:
                throw new Exception("Encode password function \"$function\" unknown");
        }
    }

    public static function encodePassword($openLdapHashedPassword, $enabled) {
        return ($enabled ? '' : rand(1000000000,9999999999)). $openLdapHashedPassword;
    }
    
    public static function decodePassword($openLdapEncodedPassword) {
        if (preg_match('/([^\{]*)(\{.+\}.+)/', $openLdapEncodedPassword,$matches)) {
            return array(
                'password' => $matches[2],
                'enabled' => strlen($matches[1]) == 0
            );
        }
        else {
            throw new Exception("\"$openLdapEncodedPassword\" is not a OpenLdap encoded password");
        }        
    }
}