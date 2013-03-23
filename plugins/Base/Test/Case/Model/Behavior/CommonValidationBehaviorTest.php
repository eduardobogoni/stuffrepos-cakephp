<?php

class CommonValidationBehaviorTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Base.BaseArticle',
        'plugin.Base.BaseAuthor',
    );

    public function setUp() {
        parent::setUp();
        $this->Article = ClassRegistry::init('BaseArticle');
        $this->Article->Behaviors->attach('Base.CommonValidation');
    }

    public function testIsUniqueInContext() {

        $this->Article->validator()->add('author_id', array(
            'rule' => array('isUniqueInContext', 'title')
        ));

        $articles = $this->Article->find('all');
        $this->assertNotEqual(empty($articles), true);

        foreach ($articles as $article) {
            $this->Article->create();
            $equalsArticle[$this->Article->alias] = array(
                'title' => $article[$this->Article->alias]['title'],
                'author_id' => $article[$this->Article->alias]['author_id'],
            );
            $this->Article->set($equalsArticle);
            $this->assertEqual($this->Article->validates(), false);
            $this->assertNotEqual(
                    empty($this->Article->validationErrors['author_id'])
                    , true
            );

            $this->Article->create();
            $notEqualsArticle[$this->Article->alias] = array(
                'title' => 'Title from ' . $article[$this->Article->alias]['id'],
                'author_id' => $article[$this->Article->alias]['author_id'],
            );
            $this->Article->set($notEqualsArticle);
            $this->assertEqual($this->Article->validates(), true);
            $this->assertEqual(
                    empty($this->Article->validationErrors['author_id'])
                    , true
            );
        }
    }

}
