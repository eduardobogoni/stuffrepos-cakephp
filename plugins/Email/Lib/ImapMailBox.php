<?php

class ImapMailBox {

    /**
     *
     * @var ImapClient
     */
    private $client;

    /**
     *
     * @var string 
     */
    private $mailBoxName;

    public function __construct(ImapClient $client, $mailBoxName) {
        $this->client = $client;
        $this->mailBoxName = $mailBoxName;
    }

    public function queryUnseenIds($sender = null) {
        $query = new Horde_Imap_Client_Search_Query();
        $query->flag(Horde_Imap_Client::FLAG_SEEN, false);
        if ($sender) {
            $query->headerText('From', $sender);
        }
        $results = $this->client->getHordeImapClient()->search($this->mailBoxName, $query);
        $ids = array();
        foreach ($results['match'] as $id) {
            $ids[] = $id;
        }
        return $ids;
    }

    public function fetchMessage($messageId) {
        $structure = $this->_getStructure($messageId);
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        foreach (array_keys($structure->contentTypeMap()) as $typeIndex) {
            $query->bodyPart($typeIndex, array(
                'peek' => true
            ));
        }
        $results = $this->client->getHordeImapClient()->fetch($this->mailBoxName, $query, array(
            'ids' => new Horde_Imap_Client_Ids($messageId)
        ));
        return $this->_parseFetchData($results[$messageId], $structure);
    }

    public function setAsSeen($messageId) {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->headerText(array(
            'peek' => false
        ));
        $results = $this->client->getHordeImapClient()->fetch($this->mailBoxName, $query, array(
            'ids' => new Horde_Imap_Client_Ids($messageId)
        ));
        if (!($results instanceof Horde_Imap_Client_Fetch_Results)) {
            throw new Exception("Failed to set message (ID=$messageId) as seen");
        }
    }

    /**
     * 
     * @param Horde_Imap_Client_Socket $client
     * @param type $messageId
     * @return Horde_Mime_Part
     */
    private function _getStructure($messageId) {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->structure();
        $results = $this->client->getHordeImapClient()->fetch($this->mailBoxName, $query, array(
            'ids' => new Horde_Imap_Client_Ids($messageId)
        ));
        return $results[$messageId]->getStructure();
    }

    private function _parseFetchData(Horde_Imap_Client_Data_Fetch $fetchData, Horde_Mime_Part $structure) {
        $data = $this->_parseFetchDataHeaders($fetchData);
        $data['bodies'] = array();
        foreach ($structure->contentTypeMap() as $typeIndex => $typeMime) {
            $data['bodies'][$typeMime] = $this->_parseFetchDataContents($fetchData, $structure, $typeIndex);
        }
        return $data;
    }

    private function _parseFetchDataHeaders(Horde_Imap_Client_Data_Fetch $fetchData) {
        $properties = array(
            'bcc', 'cc', 'date', 'from', 'in_reply_to', 'message_id',
            'reply_to', 'sender', 'subject', 'to'
        );
        $headers = array();
        foreach ($properties as $property) {
            $value = $fetchData->getEnvelope()->{$property};
            if ($value instanceof Horde_Mail_Rfc822_List) {
                $headers[$property] = $value->bare_addresses;
            } else if ($value instanceof Horde_Imap_Client_DateTime ||
                    is_string($value)) {
                $headers[$property] = $value;
            } else {
                throw new Exception('Class "' . gettype($value) . '" not mapped for property "' . $property . '"');
            }
        }
        return $headers;
    }

    private function _parseFetchDataContents(Horde_Imap_Client_Data_Fetch $fetchData, $structure, $contentsBodyPartIndex) {
        $stream = $fetchData->getBodyPart($contentsBodyPartIndex, true);
        $part = $structure[$contentsBodyPartIndex];
        $part->setContents($stream, array(
            'usestream' => true
        ));
        return $part->getContents();
    }

}
