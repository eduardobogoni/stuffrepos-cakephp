<?php

class CommonValidationBehaviorTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.StuffreposBase.Article',
        'plugin.StuffreposBase.Author',
    );

    public function setUp() {
        parent::setUp();
        $this->Article = ClassRegistry::init('Article');
        $this->Article->Behaviors->attach('StuffreposBase.CommonValidation');
    }

    public function testIsUniqueInContext() {

        $this->Article->validator()->add('author_id', array(
            'rule' => array('isUniqueInContext', 'title')
        ));

        $articles = $this->Article->find('all');
        $this->assertNotEqual(empty($articles), true);

        foreach ($articles as $article) {
            $this->Article->create();
            $equalsArticle['Article'] = array(
                'title' => $article['Article']['title'],
                'author_id' => $article['Article']['author_id'],
            );
            $this->Article->set($equalsArticle);
            $this->assertEqual($this->Article->validates(), false);
            $this->assertNotEqual(
                    empty($this->Article->validationErrors['author_id'])
                    , true
            );

            $this->Article->create();
            $notEqualsArticle['Article'] = array(
                'title' => 'Title from ' . $article['Article']['id'],
                'author_id' => $article['Article']['author_id'],
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
