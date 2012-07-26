<?php

class TraverserTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.StuffreposBase.TraverserArticle',
        'plugin.StuffreposBase.TraverserAuthor',
    );

    public function setUp() {
        parent::setUp();
        $this->Article = ClassRegistry::init('TraverserArticle');
        $this->Author = ClassRegistry::init('TraverserAuthor');
    }

    public function testBelongsToAssociation() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->findById($article[$this->Article->alias]['author_id']);
            $this->assertNotEqual($author, false);
            $this->assertEquals(isset($article[TraverserBehavior::ASSOCIATIVE_KEY]), true);            
            $this->assertEquals($article[TraverserBehavior::ASSOCIATIVE_KEY]->value($this->Article->Author->alias . '.id'), $author[$this->Author->alias]['id']);
            $this->assertEquals($article[TraverserBehavior::ASSOCIATIVE_KEY]->value($this->Article->Author->alias . '.name'), $author[$this->Author->alias]['name']);
        }
    }

}
