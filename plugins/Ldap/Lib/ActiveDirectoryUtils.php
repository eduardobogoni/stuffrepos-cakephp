<?php

class ActiveDirectoryUtils {
    // Extraído de http://adldap.sourceforge.net/wiki/doku.php?id=api_examples#disable_a_user

    const UAC_SCRIPT = 1;
    const UAC_ACCOUNTDISABLE = 2;
    const UAC_HOMEDIR_REQUIRED = 8;
    const UAC_LOCKOUT = 16;
    const UAC_PASSWD_NOTREQD = 32;
    const UAC_ENCRYPTED_TEXT_PWD_ALLOWED = 128;
    const UAC_TEMP_DUPLICATE_ACCOUNT = 256;
    const UAC_NORMAL_ACCOUNT = 512;
    const UAC_INTERDOMAIN_TRUST_ACCOUNT = 2048;
    const UAC_WORKSTATION_TRUST_ACCOUNT = 4096;
    const UAC_SERVER_TRUST_ACCOUNT = 8192;
    const UAC_DONT_EXPIRE_PASSWORD = 65536;
    const UAC_MNS_LOGON_ACCOUNT = 131072;
    const UAC_SMARTCARD_REQUIRED = 262144;
    const UAC_TRUSTED_FOR_DELEGATION = 524288;
    const UAC_NOT_DELEGATED = 1048576;
    const UAC_USE_DES_KEY_ONLY = 2097152;
    const UAC_DONT_REQ_PREAUTH = 4194304;
    const UAC_PASSWORD_EXPIRED = 8388608;
    const UAC_TRUSTED_TO_AUTH_FOR_DELEGATION = 16777216;

    public static function encodePassword($password) {
        $newPassword = '';
        $plainPassword = "\"" . $password . "\"";
        $len = strlen($plainPassword);
        for ($i = 0; $i < $len; $i++) {
            $newPassword .= "{$plainPassword{$i}}\000";
        }
        return $newPassword;
    }

    private static function _getUserAccountControlFlags() {
        return array(
            self::UAC_SCRIPT,
            self::UAC_ACCOUNTDISABLE,
            self::UAC_HOMEDIR_REQUIRED,
            self::UAC_LOCKOUT,
            self::UAC_PASSWD_NOTREQD,
            self::UAC_ENCRYPTED_TEXT_PWD_ALLOWED,
            self::UAC_TEMP_DUPLICATE_ACCOUNT,
            self::UAC_NORMAL_ACCOUNT,
            self::UAC_INTERDOMAIN_TRUST_ACCOUNT,
            self::UAC_WORKSTATION_TRUST_ACCOUNT,
            self::UAC_SERVER_TRUST_ACCOUNT,
            self::UAC_DONT_EXPIRE_PASSWORD,
            self::UAC_MNS_LOGON_ACCOUNT,
            self::UAC_SMARTCARD_REQUIRED,
            self::UAC_TRUSTED_FOR_DELEGATION,
            self::UAC_NOT_DELEGATED,
            self::UAC_USE_DES_KEY_ONLY,
            self::UAC_DONT_REQ_PREAUTH,
            self::UAC_PASSWORD_EXPIRED,
            self::UAC_TRUSTED_TO_AUTH_FOR_DELEGATION,
        );
    }

    /**
     * 
     * @param array $options
     * @return integer
     * @throws InvalidArgumentException
     */
    public static function encodeUserAccountControl($options) {
        if (!is_array($options)) {
            throw new InvalidArgumentException("options argument is not a array: " . $options);
        }

        $value = 0;
        foreach ($options as $flag => $insert) {
            if (!in_array($flag, self::_getUserAccountControlFlags())) {
                throw new InvalidArgumentException("\"$flag\" is not a valid User Account Control flag");
            }

            if ($insert) {
                $value |= $flag;
            }
        }

        return $value;
    }

    public static function decodeUserAccountControl($uacValue) {
        $options = array();
        foreach (self::_getUserAccountControlFlags() as $flag) {
            $options[$flag] = ($uacValue & $flag) != 0;
        }
        return $options;
    }

}