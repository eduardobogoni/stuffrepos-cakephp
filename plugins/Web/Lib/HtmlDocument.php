<?php

class HtmlDocument {

    /**
     *
     * @var DOMDocument
     */
    private $document;

    /**
     *
     * @var DOMNode
     */
    private $contextNode;

    public static function createFromContent($content) {
        $domDocument = new DOMDocument;

        if (!@$domDocument->loadHTML($content)) {
            throw new Exception("Falha ao tentar carregar conteúdo.");
        }

        return new HtmlDocument($domDocument);
    }

    public static function createFromUrl($url) {
        $domDocument = new DOMDocument;

        if (!@$domDocument->loadHTMLFile($url)) {
            throw new Exception("Falha ao tentar carregar \"$url\".");
        }

        return new HtmlDocument($domDocument);
    }

    private function __construct(DOMDocument $domDocument, DOMNode $contextNode = null) {
        $this->document = $domDocument;
        $this->contextNode = $contextNode;
    }

    /**
     * 
     * @param DOMNode $node
     * @return \HtmlDocument
     */
    public function createFromNode(DOMNode $node) {
        return new HtmlDocument($this->document, $node);
    }

    /**
     * 
     * @param string $xpathQuery
     * @return DOMNodeList
     * @throws Exception
     */
    public function queryNodes($xpathQuery) {
        $xpath = new DOMXPath($this->document);
        $xpathQuery = str_replace('x:', '', $xpathQuery);
        $result = $xpath->query($xpathQuery, $this->contextNode);

        if (!$result) {
            throw new Exception("Fail on XPath query \"$xpathQuery\".");
        }

        return $result;
    }

    public function queryUniqueNode($xpathQuery) {
        foreach ($this->queryNodes($xpathQuery) as $node) {
            return $node;
        }

        return null;
    }

    public function queryValues($xpathQuery) {
        $values = array();
        foreach ($this->queryNodes($xpathQuery) as $node) {
            $values[] = $node->nodeValue;
        }

        return $values;
    }

    public function queryUniqueValue($xpathQuery) {
        foreach ($this->queryValues($xpathQuery) as $value) {
            return $value;
        }

        return null;
    }

    /**
     * 
     * @return DOMDocument
     */
    public function getDocument() {
        return $this->document;
    }

    /**
     * 
     * @return DOMNode
     */
    public function getContextNode() {
        return $this->contextNode;
    }

    }

?>
