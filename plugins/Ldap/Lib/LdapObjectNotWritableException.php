<?php

class LdapObjectNotWritableException extends Exception {

    public function __construct(\Model $model, $dn, $parentDn) {
        parent::__construct("Model: \"{$model->name}\" / DN: \"{$dn}\" / Parent DN: \"{$parentDn}\"");
    }

}
