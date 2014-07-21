<?php

App::uses('ModelTraverser', 'Base.Lib');
App::uses('BaseArticleFixture', 'Base.Test/Fixture');
App::uses('BaseAuthorFixture', 'Base.Test/Fixture');

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

    public function findArticlesProvider() {
        $articleFixture = new BaseArticleFixture();
        $authorFixture = new BaseAuthorFixture();
        $authors = array();
        foreach ($authorFixture->records as $record) {
            $authors[$record['id']] = $record;
        }
        $ret = array();
        foreach ($articleFixture->records as $record) {
            $ret[] = array(
                array(
                    'BaseArticle' => $record,
                    'Author' => ($record['author_id']) ?
                            $authors[$record['author_id']] :
                            array()
                )
            );
        }
        return $ret;
    }

    public function findArticlesWithFieldsProvider() {
        $ret = array();
        foreach ($this->findArticlesProvider() as $data) {
            foreach ($data[0] as $alias => $aliasData) {
                foreach (array_keys($aliasData) as $field) {
                    $ret[] = array(
                        $data[0],
                        $alias,
                        $field
                    );
                }
            }
        }
        return $ret;
    }

    public function findAuthorsProvider() {
        $articleFixture = new BaseArticleFixture();
        $authorFixture = new BaseAuthorFixture();
        $ret = array();
        foreach ($authorFixture->records as $record) {
            $author = array(
                'BaseAuthor' => $record,
                'Article' => array(),
            );
            foreach ($articleFixture->records as $article) {
                if ($article['author_id'] == $record['id']) {
                    $author['Article'][] = $article;
                }
            }
            $ret[] = array($author);
        }
        return $ret;
    }

    /**
     * @dataProvider findArticlesWithFieldsProvider
     */
    public function testFindField($article, $alias, $field) {
        $this->assertEqual(
                ModelTraverser::_findField(
                        ($this->Article->alias == $alias ? $this->Article : $this->Article->{$alias})
                        , $article
                        , $field
                )
                , $article[$alias][$field]
        );
        $this->assertEqual(
                ModelTraverser::_findField(
                        ($this->Article->alias == $alias ? $this->Article : $this->Article->{$alias})
                        , $article[$alias]
                        , $field
                )
                , $article[$alias][$field]
        );
    }

    /**
     * @dataProvider findArticlesProvider
     */
    public function testFindSelf($article) {
        $this->assertEqual(
                ModelTraverser::_findSelf($this->Article, $article)
                , $article[$this->Article->alias]
        );
    }

    /**
     * @dataProvider findArticlesProvider
     */
    public function testFindHasOne($article) {
        $this->assertEqual(
                ModelTraverser::_findHasOne($this->Article, $article, $this->Article->Author->alias)
                , $article[$this->Article->Author->alias]
        );
        $result = $article[$this->Article->Author->alias];
        unset($article[$this->Article->Author->alias]);
        $this->assertEqual(
                ModelTraverser::_findHasOne($this->Article, $article, $this->Article->Author->alias)
                , $result
        );
    }

    /**
     * @dataProvider findAuthorsProvider
     */
    public function testFindHasMany($author) {
        $this->assertEqual(
                ModelTraverser::_findHasMany(
                        $this->Author
                        , $author
                        , $this->Author->Article->alias
                )
                , $author[$this->Author->Article->alias]
        );
        $result = $author[$this->Author->Article->alias];
        unset($author[$this->Author->Article->alias]);
        $this->assertEqual(
                ModelTraverser::_findHasMany(
                        $this->Author
                        , $author
                        , $this->Author->Article->alias
                )
                , $result
        );
    }

    /**
     * @dataProvider findArticlesWithFieldsProvider
     * @depends testFindHasMany
     * @depends testFindHasOne
     * @depends testFindField
     * @depends testFindSelf
     */
    public function testFind($article, $alias, $field) {
        $this->assertEqual(
                ModelTraverser::find(
                        $this->Article
                        , $article
                        , "$alias.$field"
                )
                , array(
            array(
                'value' => $article[$alias],
                'model' => $this->_model($this->Article, $alias),
            ),
            array(
                'value' => $article[$alias][$field],
                'model' => $this->_model($this->Article, $alias),
            ),
                )
        );
    }

    /**
     * @dataProvider findArticlesWithFieldsProvider
     * 
     */
    public function testFieldValueWithSelfAlias($article, $alias, $field) {
        $this->assertEqual(
                ModelTraverser::value($this->Article, $article, "$alias.$field")
                , $article[$alias][$field]
        );
        $this->assertEqual(
                ModelTraverser::value(
                        $this->Article
                        , $article[$this->Article->alias]
                        , "$alias.$field"
                )
                , $article[$alias][$field]
        );
    }

    /**
     * @dataProvider findArticlesProvider
     * @depends testFind
     */
    public function testFieldValueWithoutSelfAlias($article) {
        foreach ($article[$this->Article->alias] as $field => $value) {
            $this->assertEqual(
                    ModelTraverser::value($this->Article, $article, $field)
                    , $article[$this->Article->alias][$field]
            );
        }
    }

    /**
     * @dataProvider findArticlesProvider
     * @depends testFieldValueWithSelfAlias
     * @depends testFieldValueWithoutSelfAlias
     */
    public function testBelongsToValue($article) {
        $author = $this->Author->findById(
                $article[$this->Article->alias]['author_id']
        );

        if ($article[$this->Article->alias]['author_id']) {
            $this->assertNotEqual($author, false);
            $this->assertNotEqual($author, array());
        } else {
            $this->assertEqual($author, array());
        }

        foreach (array_keys($this->Author->schema()) as $authorField) {
            $this->assertEquals(
                    ModelTraverser::value(
                            $this->Article
                            , $article
                            , $this->Article->Author->alias . '.' . $authorField
                    )
                    , isset($author[$this->Author->alias][$authorField]) ?
                            $author[$this->Author->alias][$authorField] :
                            null
            );
        }
    }

    /**
     * @dataProvider findAuthorsProvider
     * @depends testFieldValueWithSelfAlias
     * @depends testFieldValueWithoutSelfAlias
     */
    public function testHasManyValue($author) {
        $records = $this->Author->Article->findAllByAuthorId(
                $author[$this->Author->alias]['id']
        );
        $articles = array();
        foreach ($records as $r) {
            $articles[] = $r[$this->Author->Article->alias];
        }
        $this->assertEqual(
                ModelTraverser::value($this->Author, $author, $this->Author->Article->alias)
                , $articles
        );
    }

    /**
     * @dataProvider findArticlesWithFieldsProvider
     * @depends testFieldValueWithSelfAlias
     * @depends testFieldValueWithoutSelfAlias
     */
    public function testDisplayValue($article, $alias, $field) {
        $actual = ModelTraverser::displayValue($this->Article, $article, "$alias.$field");
        if ($alias == $this->Article->alias && $field == 'author_id') {
            $this->assertEqual(
                    $actual
                    , (
                    empty($article[$this->Article->Author->alias][$this->Article->Author->displayField]) ?
                            null :
                            $article[$this->Article->Author->alias][$this->Article->Author->displayField]
                    )
            );
        } else {
            $this->assertEqual(
                    $actual
                    , $article[$alias][$field]
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

    private function _model(\Model $model, $alias) {
        return ($model->alias == $alias ? $model : $model->{$alias});
    }

}
