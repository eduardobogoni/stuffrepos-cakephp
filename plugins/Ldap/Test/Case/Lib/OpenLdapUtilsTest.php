<?php

App::uses('OpenLdapUtils', 'Ldap.Lib');

class OpenLdapUtilsTest extends CakeTestCase {

    public function testEncodePassword() {
        $hashedPassword = OpenLdapUtils::hashPassword('123456');

        $this->assertEqual(
            OpenLdapUtils::encodePassword($hashedPassword, true)
            , $hashedPassword);

        $this->assertNotEqual(
            OpenLdapUtils::encodePassword($hashedPassword, false)
            , $hashedPassword);
    }

    public function testDecodePassword() {
        $hashedPassword = OpenLdapUtils::hashPassword('123456');

        $this->assertEqual(
            OpenLdapUtils::decodePassword(
                OpenLdapUtils::encodePassword($hashedPassword, true)
            )
            , array(
            'password' => $hashedPassword,
            'enabled' => true
        ));

        $this->assertEqual(
            OpenLdapUtils::decodePassword(
                OpenLdapUtils::encodePassword($hashedPassword, false)
            )
            , array(
            'password' => $hashedPassword,
            'enabled' => false
        ));
    }

}