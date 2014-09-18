<?php

App::uses('PlancakeEmailParser', 'Email.Lib/PlancakeEmailParser');

class MailParser {

    public static function parseFile($path) {
        return self::parse(file_get_contents($path));
    }

    public static function parse($content) {
        $main = self::_parseRawMail($content);
        $ret = array();
        foreach (array('to', 'from', 'bcc', 'cc') as $header) {
            $ret[$header] = empty($main['headers'][$header]) ?
                    array() :
                    self::_buildAddressList($main['headers'][$header]);
        }
        foreach (array('reply_to', 'sender') as $header) {
            $ret[$header] = empty($main['headers'][$header]) ?
                    self::_buildAddressList($main['headers']['from']) :
                    self::_buildAddressList($main['headers'][$header]);
        }
        foreach (array('subject', 'in_reply_to', 'message-id') as $header) {
            $ret[str_replace('-', '_', $header)] = empty($main['headers'][$header]) ? '' : $main['headers'][$header];
        }

        $ret['date'] = new Horde_Imap_Client_DateTime($main['headers']['date']);
        $ret['bodies'] = self::_buildBodies($main);
        return $ret;
    }

    private static function _mimeDecode($string) {
        return iconv_mime_decode($string, 0, mb_internal_encoding());
    }

    private static function _parseRawMail($content) {
        $mimemail = mailparse_msg_create();
        mailparse_msg_parse($mimemail, $content);
        $parsedData = mailparse_msg_get_part_data($mimemail);
        $parsedData['body'] = self::_extractBody($parsedData, $content);
        if (!empty($parsedData['headers']['subject'])) {
            $parsedData['headers']['subject'] = self::_mimeDecode($parsedData['headers']['subject']);
        }
        mailparse_msg_free($mimemail);
        return $parsedData;
    }

    private static function _buildBodies($parsedData) {
        if (strpos($parsedData['content-type'],'multipart/') === 0) {
            return self::_buildBodiesFromMultipart($parsedData['body'], $parsedData['content-boundary']);
        } else {
            return array(
                $parsedData['content-type'] => self::_mimeDecode($parsedData['body'])
            );
        }
    }

    private static function _buildBodiesFromMultipart($body, $boundary) {
        $parts = explode("--" . $boundary, $body);
        array_shift($parts);
        array_pop($parts);
        $bodies = array();
        foreach ($parts as $part) {
            $partRawData = preg_replace("/\r\n\$/", '', ltrim($part));
            $parsedData = self::_parseRawMail($partRawData);
            $bodies[$parsedData['content-type']] = $parsedData['body'];
        }
        return $bodies;
    }

    private static function _extractBody($parsedData, $content) {
        $body = substr($content, $parsedData['starting-pos-body']);
        if (!empty($parsedData['transfer-encoding']) && $parsedData['transfer-encoding'] == 'quoted-printable') {
            $body = quoted_printable_decode($body);
        }
        return $body;
    }

    private static function _buildAddressList($string) {
        return array_map(function($v) {
            return $v['address'];
        }, mailparse_rfc822_parse_addresses($string));
    }

}
