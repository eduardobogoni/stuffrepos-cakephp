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

    public function testBelongsToAssociationValue() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->findById(
                    ModelTraverser::value($this->Article, $article, 'author_id')
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

    public function testBelongsToAssociationInstance() {
        $articles = $this->Article->find('all');

        foreach ($articles as $article) {
            $author = $this->Author->findById(
                    ModelTraverser::value($this->Article, $article, 'author_id')
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
