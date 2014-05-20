<?php

App::uses('ImapMailBox', 'Email.Lib');

class ImapClient {

    /**
     *
     * @var Horde_Imap_Client_Socket
     */
    private $client;

    public function __construct($hordeImapClientSettings = array()) {
        $this->client = new Horde_Imap_Client_Socket($hordeImapClientSettings);
    }

    /**
     * 
     * @param string $config
     * @return ImapClient
     */
    public static function createFromEmailConfig($config = 'default') {
        return new ImapClient(self::_hordeImapClientsFromEmailConfig($config));
    }

    private static function _hordeImapClientsFromEmailConfig($config) {
        config('email');
        $emailConfig = new EmailConfig();
        return array(
            'username' => $emailConfig->{$config}['username'],
            'password' => $emailConfig->{$config}['password'],
            'hostspec' => $emailConfig->{$config}['imap_host'],
            'port' => $emailConfig->{$config}['imap_port'],
            'secure' => $emailConfig->{$config}['imap_secure'],
        );
    }

    /**
     * 
     * @return Horde_Imap_Client_Socket
     */
    public function getHordeImapClient() {
        return $this->client;
    }

    public function createMailBox($mailBoxName) {
        return new ImapMailBox($this, $mailBoxName);
    }

}
