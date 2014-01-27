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
        $rdns = explode(',', $dn);
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
    
    public static function firstRdn($dn, $field = null) {
        $explodedDn = self::explodeDn($dn);

        switch ($field) {
            case 'attribute':
            case 'value':
                $rdn = self::explodeRdn($explodedDn[0]);
                return $rdn[$field];

            default:
                return $explodedDn[0];
        }
    }

    public static function joinDns($dn1, $dn2) {
        $dn1 = trim($dn1);
        $dn2 = trim($dn2);
        if ($dn1 != '' && $dn2 != '') {
            return $dn1 . ',' . $dn2;
        } else {
            return $dn1 . $dn2;
        }
    }

    public static function parentDn($dn) {
        $rdns = self::explodeDn($dn, true);
        array_shift($rdns);
        if (empty($rdns)) {
            return false;
        } else {
            return self::implodeDn($rdns, true);
        }
    }

    public static function isDnParent($parentDn, $childDn) {
        $parentRdns = array_reverse(self::explodeDn($parentDn));
        $childRdns = array_reverse(self::explodeDn($childDn));
        if (count($childRdns) <= count($parentRdns)) {
            return false;
        }
        for ($i = 0; $i < count($parentRdns); $i++) {
            if (strtolower($parentRdns[$i]) != strtolower($childRdns[$i])) {
                return false;
            }
        }
        return true;
    }

}