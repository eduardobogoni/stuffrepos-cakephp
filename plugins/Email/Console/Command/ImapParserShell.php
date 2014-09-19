<?php

App::uses('AdminNotify', 'Lib');
App::uses('ItauMailParser', 'Lib');
App::uses('ImapClient', 'Email.Lib');

class ImapParserShell extends AppShell {

    public function main() {
        $mailBox = ImapClient::createFromEmailConfig()->createMailBox('INBOX');
        $ids = $mailBox->queryUnseenIds();
        $this->out('<info>Mensagens encontradas:</info> ' . count($ids));
        $this->hr();
        foreach ($ids as $id) {
            $this->_parseMail($mailBox, $id);
        }
    }

    private function _parseMail(ImapMailBox $mailBox, $messageId) {
        $this->out('Fetching message with ID = ' . $messageId . ' ...');
        try {
            $mailData = $mailBox->fetchMessage($messageId);
            $this->out('Subject: ' . $mailData['subject']);
            $this->out('From: ' . $mailData['from'][0]);
            $this->_parseMailData($mailBox, $messageId, $mailData);
        } catch (Exception $ex) {
            $this->out("<error>Error parsing mail: {$ex->getMessage()}</error>");
        }
        $this->hr();
    }

    private function _parseMailData(ImapMailBox $mailBox, $messageId, $mailData) {
        if (($parser = $this->_getParser($mailData))) {
            $this->out('Parser found: ' . get_class($parser));
            $parser->parse($mailData);
            $mailBox->setAsSeen($messageId, true);
        } else {
            $this->out('<error>Parser not found</error>');
        }
    }

    private function _getParser($mailData) {
        foreach (ClassSearcher::findInstances('Lib/EmailParser') as $parser) {
            if ($parser->accept($mailData)) {
                return $parser;
            }
        }
        return false;
    }

}
