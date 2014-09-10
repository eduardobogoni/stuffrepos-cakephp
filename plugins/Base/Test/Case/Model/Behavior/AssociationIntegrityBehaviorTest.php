<?php

/**
 * @property \BaseArticle $Article
 */
class AssociationIntegrityBehaviorTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Base.BaseArticle',
        'plugin.Base.BaseAuthor',
        'plugin.Base.BaseCategory',
    );

    public function setUp() {
        parent::setUp();
        $this->Article = ClassRegistry::init('BaseArticle');
        $this->Article->Behaviors->load('Base.AssociationIntegrity');
        $this->Article->Category->Behaviors->load('Base.AssociationIntegrity');
    }

    public function tearDown() {
        unset($this->Article);
        parent::tearDown();
    }

    public function testCreateWithEmptyData() {
        $this->Article->create();
        $result = $this->Article->save(array($this->Article->alias => array()));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Article->validationErrors), false);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), false);
    }

    public function testCreateWithoutForeignKey() {
        $this->Article->create();
        $result = $this->Article->save(array($this->Article->alias => array(
                'title' => 'Teste',
        )));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Article->validationErrors), false);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), true);
    }

    public function testCreateWithNoExistingForeignKey() {
        $this->Article->create();
        $result = $this->Article->save(array($this->Article->alias => array(
                'title' => 'Teste',
                'author_id' => 4567,
                'category_id' => 12345
        )));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Article->validationErrors), true);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), true);
    }

    public function testCreateWithValidForeignKey() {
        $this->Article->Category->create();
        $result = $this->Article->Category->save(array('Category' => array(
                'title' => 'Category Teste'
        )));
        $this->assertNotEqual($result, false);
        $this->Article->create();
        $result = $this->Article->save(array($this->Article->alias => array(
                'title' => 'Teste',
                'category_id' => $this->Article->Category->id,
        )));
        $this->assertNotEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), false);
    }

    /**
     * @depends testCreateWithValidForeignKey
     */
    public function testEditWithoutForeignKey() {
        $article = $this->Article->find('first');
        $this->assertEqual(empty($article), false);
        unset($article[$this->Article->alias]['category_id']);
        $result = $this->Article->save($article);
        $this->assertNotEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), false);
    }

    /**
     * @depends testEditWithoutForeignKey
     */
    public function testEditWithNullForeignKey() {
        $article = $this->Article->find('first');
        $this->assertEqual(empty($article), false);
        $article[$this->Article->alias]['category_id'] = null;
        $result = $this->Article->save($article);
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Article->validationErrors), true);
    }

    public function testDeleteWithAssociations() {
        $category = $this->Article->Category->find('first');
        $this->assertEqual(empty($category), false);
        $this->assertEqual($this->Article->hasAny(array(
                    $this->Article->alias . '.category_id' => $category['Category']['id']
                )), true);
        $this->assertNotEqual($this->Article->Category->delete($category['Category']['id']), true);
        $this->assertNotEqual($this->Article->Category->exists($category['Category']['id']), false);
    }

    public function testDeleteWithoutAssociations() {
        $category = $this->Article->Category->find('first');
        $this->assertEqual(empty($category), false);
        $this->Article->deleteAll(array(
            $this->Article->alias . '.category_id' => $category['Category']['id']
        ));
        $this->assertEqual($this->Article->hasAny(array(
                    $this->Article->alias . '.category_id' => $category['Category']['id']
                )), false);
        $this->assertEqual($this->Article->Category->delete($category['Category']['id']), true);
        $this->assertEqual($this->Article->Category->exists($category['Category']['id']), false);
    }

}
