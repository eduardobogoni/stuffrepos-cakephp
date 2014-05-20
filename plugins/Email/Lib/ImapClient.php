<?php

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
     * @param Horde_Imap_Client_Socket $client
     * @return array
     */
    public function fetchInboxUnseenIds($sender) {
        $query = new Horde_Imap_Client_Search_Query();
        $query->flag(Horde_Imap_Client::FLAG_SEEN, false);
        $query->headerText('From', $sender);
        $results = $this->client->search('INBOX', $query);
        $ids = array();
        foreach ($results['match'] as $id) {
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * 
     * @return Horde_Imap_Client_Socket
     */
    public function getHordeImapClient() {
        return $this->client;
    }

}
