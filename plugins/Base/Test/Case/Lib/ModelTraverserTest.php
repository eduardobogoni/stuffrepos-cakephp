<?php

App::import('ModelTraverser', 'Base.Lib');

class ModelTraverserTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Base.BaseArticle',
        'plugin.Base.BaseAuthor',
    );

    public function setUp() {
        parent::setUp();
        $this->Article = ClassRegistry::init('BaseArticle');
        $this->Author = ClassRegistry::init('BaseAuthor');
    }

    public function testValue() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            foreach (array_keys($this->Article->schema()) as $field) {
                $this->assertEqual(
                        ModelTraverser::value($this->Article, $article, $field)
                        , $article[$this->Article->alias][$field]
                );
            }
        }
    }

    public function testValueBelongsTo() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->findById(
                    $article[$this->Article->alias]['author_id']
            );

            if ($article[$this->Article->alias]['author_id']) {                
                $this->assertNotEqual($author, false);
                $this->assertNotEqual($author, array());
            }
            else {
                $this->assertEqual($author, array());
            }

            foreach (array_keys($this->Author->schema()) as $authorField) {
                $this->assertEquals(
                        ModelTraverser::value(
                                $this->Article
                                , $article
                                , $this->Article->Author->alias . '.' . $authorField
                        )
                        , isset($author[$this->Author->alias][$authorField]) ? $author[$this->Author->alias][$authorField] : null
                );
            }
        }
    }

    public function testValueHasMany() {
        $authors = $this->Author->find('all');

        foreach ($authors as $author) {
            $articles = $this->Author->Article->findAllByAuthorId(
                    $author[$this->Author->alias]['id']
            );

            $this->assertEqual(
                    ModelTraverser::value($this->Author, $author, $this->Author->Article->alias)
                    , $articles
            );
        }
    }

    public function testDisplayValue() {        
        foreach ($this->Article->find('all') as $article) {
            $author = $this->Author->findById(
                    $article[$this->Article->alias]['author_id']
            );
            if ($article[$this->Article->alias]['author_id']) {                
                $this->assertNotEqual($author, false);
                $this->assertNotEqual($author, array());
            }
            else {
                $this->assertEqual($author, array());
            }
            $this->assertEquals(
                    ModelTraverser::displayValue(
                            $this->Article
                            , $article
                            , 'author_id'
                    )
                    , isset($author[$this->Author->alias][$this->Author->displayField]) ? $author[$this->Author->alias][$this->Author->displayField] : null
            );
        }
    }

    public function testSchemaByPath() {
        foreach ($this->Article->schema() as $field => $schema) {
            $this->assertEqual(ModelTraverser::schema($this->Article, $field), $schema);
        }
    }

    public function testSchemaByPathBelongsToAssociation() {
        foreach ($this->Article->Author->schema() as $field => $schema) {
            $this->assertEqual(
                    ModelTraverser::schema(
                            $this->Article
                            , "{$this->Article->Author->alias}.$field"
                    )
                    , $schema
            );
        }
    }

}
