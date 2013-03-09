<?php

App::uses('LdapUtils', 'Ldap.Lib');

class LdapUtilsTest extends CakeTestCase {

    public function testExplodeRdn() {

        $this->assertEqual(
                LdapUtils::explodeRdn('cn=Joao')
                , array(
            'attribute' => 'cn',
            'value' => 'Joao'
                )
        );
    }

    public function testExplodeDnWithoutExplodeRdn() {
        $this->assertEqual(
                LdapUtils::explodeDn('cn=Joao,dc=nodomain', false)
                , array(
            'cn=Joao',
            'dc=nodomain'
                )
        );
    }

    public function testExplodeDnWithExplodeRdn() {
        $this->assertEqual(
                LdapUtils::explodeDn('cn=Joao,dc=nodomain', true)
                , array(
            array('attribute' => 'cn', 'value' => 'Joao'),
            array('attribute' => 'dc', 'value' => 'nodomain'),
                )
        );
    }

    public function testImplodeDnWithoutExplodeRdn() {
        $this->assertEqual(
                LdapUtils::implodeDn(array(
                    'cn=Joao',
                    'dc=nodomain'
                        )
                        , false)
                , 'cn=Joao,dc=nodomain'
        );
    }

    public function testImplodeDnWithExplodeRdn() {
        $this->assertEqual(
                LdapUtils::implodeDn(array(
                    array('attribute' => 'cn', 'value' => 'Joao'),
                    array('attribute' => 'dc', 'value' => 'nodomain'),
                        )
                        , true
                )
                , 'cn=Joao,dc=nodomain'
        );
    }

    public function testNormalizeDn() {
        $this->assertEqual(
                LdapUtils::normalizeDn('Cn=Joao,Dc=nodomain')
                , 'cn=Joao,dc=nodomain'
        );
    }

}