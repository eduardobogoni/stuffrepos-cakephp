<?php

App::uses('HtmlDocument', 'Web.Lib');

class HtmlDocumentTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
        $this->content = <<<EOT
<html>
    <head>
        <title>Books</title>
    </head>
    <body>
        <h1>Books</h1>
        <p>
            <strong>Book A</strong>
            <span>Value 1</span>
        </p>
        <p>
            <strong>Book B</strong>
            <span>Value 2</span>
        </p>
    </body>
</html>
EOT;
    }

    public function testQueryWithContextNode() {
        $htmlDocument = HtmlDocument::createFromContent($this->content);
        $paragraphNodes = $htmlDocument->queryNodes('//x:p');
        $this->assertEqual($paragraphNodes->length, 2);
        foreach ($paragraphNodes as $i => $paragraphNode) {
            $subHtmlDocument = $htmlDocument->createFromNode($paragraphNode);
            $this->assertEqual(
                    $subHtmlDocument->queryValues('.//span/text()')
                    , array(
                'Value ' . ($i + 1)
                    )
            );
            $this->assertEqual($subHtmlDocument->queryUniqueValue('.//span/text()'), 'Value ' . ($i + 1));
        }
    }

}
