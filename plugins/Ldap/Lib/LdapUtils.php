<?php

class LdapUtils {

    public static function normalizeDn($dn) {
        $rdns = self::explodeDn($dn, true);
        $newRdns = array();
        foreach ($rdns as $rdn) {
            $newRdns[] = array(
                'attribute' => strtolower($rdn['attribute']),
                'value' => trim($rdn['value'])
            );
        }

        return self::implodeDn($newRdns, true);
    }

    public static function explodeDn($dn, $explodeRdns = false) {
        $rdns = ldap_explode_dn($dn, 0);
        unset($rdns['count']);

        if ($explodeRdns) {
            $explodedRdns = array();
            foreach ($rdns as $rdn) {
                $explodedRdns[] = self::explodeRdn($rdn);
            }
            return $explodedRdns;
        } else {
            return $rdns;
        }
    }

    public static function explodeRdn($rdn) {
        $equalsSymbolPosition = strpos($rdn, '=');

        if ($equalsSymbolPosition === false) {
            throw new Exception("RDN \"$rdn\" has no equals symbol");
        }

        return array(
            'attribute' => substr($rdn, 0, $equalsSymbolPosition),
            'value' => substr($rdn, $equalsSymbolPosition + 1)
        );
    }

    public static function implodeDn($rdns, $isExplodedRdns) {
        if ($isExplodedRdns) {
            $implodedRdns = array();
            foreach ($rdns as $rdn) {
                $implodedRdns[] = "{$rdn['attribute']}={$rdn['value']}";
            }
            $rdns = $implodedRdns;
        }

        return implode(',', $rdns);
    }

}