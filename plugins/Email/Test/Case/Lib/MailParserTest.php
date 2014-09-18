<?php

App::uses('MailParser', 'Email.Lib');

class MailParserTest extends CakeTestCase {

    public function testParseMail1() {
        $mailData = MailParser::parseFile(dirname(__FILE__) . DS . 'mail1');
        $mailDataDate = $mailData['date'];
        unset($mailData['date']);
        $expectedDate = new DateTime('2014-06-13 18:18:41 -03:00');
        $this->assertEqual($mailDataDate instanceof DateTime, true);
        $this->assertEqual($mailDataDate->getTimestamp(), $expectedDate->getTimestamp());
        $this->assertEqual($mailDataDate->getOffset(), $expectedDate->getOffset());
        $this->assertEqual($mailData, array(
            'bcc' => array(),
            'cc' => array(),
            'from' => array(
                'eduardobogoni@gmail.com'
            ),
            'in_reply_to' => '',
            'message_id' => '<CAAGzLnaN1dn+dAsPeCrCyy5c_dU2ytfQ3qvNmjM7T00QdcLu8A@mail.gmail.com>',
            'reply_to' => array(
                'eduardobogoni@gmail.com'
            ),
            'sender' => array(
                'eduardobogoni@gmail.com'
            ),
            'subject' => 'Hello',
            'to' => array(
                'ehbrsmailer@gmail.com'
            ),
            'bodies' => array(
                'text/plain' => 'Hello World!' . "\r\n",
                'text/html' => '<div dir="ltr">Hello World!<br></div>' . "\r\n",
            )
        ));
    }

    public function testParseMail2() {
        $mailData = MailParser::parseFile(dirname(__FILE__) . DS . 'mail2');
        $mailDataDate = $mailData['date'];
        unset($mailData['date']);
        $expectedDate = new DateTime('2014-06-13 22:22:40 -03:00');
        $this->assertEqual($mailDataDate instanceof DateTime, true);
        $this->assertEqual($mailDataDate->getTimestamp(), $expectedDate->getTimestamp());
        $this->assertEqual($mailDataDate->getOffset(), $expectedDate->getOffset());
        $this->assertEqual($mailData, array(
            'bcc' => array(),
            'cc' => array(),
            'from' => array(
                'eduardobogoni@gmail.com'
            ),
            'in_reply_to' => '',
            'message_id' => '<CAAGzLnbD57Z6BX6SxMbdffgOZ8X3p=oSMx1-RWp=64jsyym6JQ@mail.gmail.com>',
            'reply_to' => array(
                'eduardobogoni@gmail.com'
            ),
            'sender' => array(
                'eduardobogoni@gmail.com'
            ),
            'subject' => 'Palavras com acentos (Acento no assunto: é)',
            'to' => array(
                'eduardobogoni@gmail.com',
                'ehbrsmailer@gmail.com'
            ),
            'bodies' => array(
                'text/plain' => 'Acento no corpo da mensagem: á' . "\r\n",
                'text/html' => '<div dir="ltr">Acento no corpo da mensagem: á<br></div>' . "\r\n"
            )
                )
        );
    }

    public function testMultiBody() {
        $mailData = MailParser::parseFile(dirname(__FILE__) . DS . 'multibody-source');
        $this->assertEqual(count($mailData['bodies']), 2);
        $k = 0;
        foreach ($mailData['bodies'] as $bodyType => $bodyContent) {
            $expectedValue = file_get_contents(dirname(__FILE__) . DS . 'multibody-' . ($k++));
            $this->assertEqual($bodyContent, $expectedValue);
            
        }
    }

}
