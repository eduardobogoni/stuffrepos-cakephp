<?php

class ModelTraverserTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.StuffreposBase.TraverserArticle',
        'plugin.StuffreposBase.TraverserAuthor',
    );

    public function setUp() {
        parent::setUp();
        App::import('Lib', 'StuffreposBase.ModelTraverser');
        $this->Article = ClassRegistry::init('TraverserArticle');
        $this->Article->alias = 'Article';
        $this->Author = ClassRegistry::init('TraverserAuthor');
        $this->Author->alias = 'Author';
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
            $this->assertNotEqual($author, false);


            foreach (array_keys($this->Author->schema()) as $authorField) {
                $this->assertEquals(
                        ModelTraverser::value(
                                $this->Article
                                , $article
                                , $this->Article->Author->alias . '.' . $authorField
                        )
                        , $author[$this->Author->alias][$authorField]
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

    public function testlastInstanceBelongsTo() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->find(
                    'first',
                    array(
                        'conditions' => array(
                            'id' => ModelTraverser::value($this->Article, $article, 'author_id')
                        ),
                        'recursive' => 0,
                    )
                    
            );
            $this->assertNotEqual($author, false);

            foreach (array_keys($this->Author->schema()) as $authorField) {
                $this->assertEquals(
                        ModelTraverser::lastInstance(
                                $this->Article
                                , $article
                                , $this->Article->Author->alias . '.' . $authorField
                        )
                        , $author
                );
            }
        }
    }

    public function testBelongsToAssociationLastInstancePrimaryKey() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->findById(
                    ModelTraverser::value($this->Article, $article, 'author_id')
            );
            $this->assertNotEqual($author, false);

            foreach (array_keys($this->Author->schema()) as $authorField) {
                $this->assertEquals(
                        ModelTraverser::lastAssociationPrimaryKeyValue(
                                $this->Article
                                , $article
                                , $this->Article->Author->alias . '.' . $authorField
                        )
                        , $author[$this->Author->alias][$this->Author->primaryKey]
                );
            }
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
