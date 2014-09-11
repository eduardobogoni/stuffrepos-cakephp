<?php

/**
 * @property \BaseBook $Book
 */
class AssociationIntegrityBehaviorTest extends CakeTestCase {

    public $fixtures = array(
        'plugin.Base.BaseBook',
        'plugin.Base.BaseArticle',
        'plugin.Base.BaseAuthor',
        'plugin.Base.BaseCategory',
    );

    public function setUp() {
        parent::setUp();
        $this->Book = ClassRegistry::init('BaseBook');
        $this->Book->Behaviors->load('Base.AssociationIntegrity');
        $this->Book->Category->Behaviors->load('Base.AssociationIntegrity');
    }

    public function tearDown() {
        unset($this->Book);
        parent::tearDown();
    }

    public function testCreateWithEmptyData() {
        $this->Book->create();
        $result = $this->Book->save(array($this->Book->alias => array()));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Book->validationErrors), false);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), false);
    }

    public function testCreateWithoutForeignKey() {
        $this->Book->create();
        $result = $this->Book->save(array($this->Book->alias => array(
                'title' => 'Teste',
        )));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Book->validationErrors), false);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), true);
    }

    public function testCreateWithNoExistingForeignKey() {
        $this->Book->create();
        $result = $this->Book->save(array($this->Book->alias => array(
                'title' => 'Teste',
                'author_id' => 4567,
                'category_id' => 12345
        )));
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('author_id', $this->Book->validationErrors), true);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), true);
    }

    public function testCreateWithValidForeignKey() {
        $this->Book->Category->create();
        $result = $this->Book->Category->save(array('Category' => array(
                'title' => 'Category Teste'
        )));
        $this->assertNotEqual($result, false);
        $this->Book->create();
        $result = $this->Book->save(array($this->Book->alias => array(
                'title' => 'Teste',
                'category_id' => $this->Book->Category->id,
        )));
        $this->assertNotEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), false);
    }

    /**
     * @depends testCreateWithValidForeignKey
     */
    public function testEditWithoutForeignKey() {
        $book = $this->Book->find('first');
        $this->assertEqual(empty($book), false);
        unset($book[$this->Book->alias]['category_id']);
        $result = $this->Book->save($book);
        $this->assertNotEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), false);
    }

    /**
     * @depends testEditWithoutForeignKey
     */
    public function testEditWithNullForeignKey() {
        $book = $this->Book->find('first');
        $this->assertEqual(empty($book), false);
        $book[$this->Book->alias]['category_id'] = null;
        $result = $this->Book->save($book);
        $this->assertEqual($result, false);
        $this->assertEqual(array_key_exists('category_id', $this->Book->validationErrors), true);
    }

    public function testDeleteWithAssociations() {
        $category = $this->Book->Category->find('first');
        $this->assertEqual(empty($category), false);
        $this->assertEqual($this->Book->hasAny(array(
                    $this->Book->alias . '.category_id' => $category['Category']['id']
                )), true);
        $this->assertNotEqual($this->Book->Category->delete($category['Category']['id']), true);
        $this->assertNotEqual($this->Book->Category->exists($category['Category']['id']), false);
    }

    public function testDeleteWithoutAssociations() {
        $category = $this->Book->Category->find('first');
        $this->assertEqual(empty($category), false);
        $this->Book->deleteAll(array(
            $this->Book->alias . '.category_id' => $category['Category']['id']
        ));
        $this->assertEqual($this->Book->hasAny(array(
                    $this->Book->alias . '.category_id' => $category['Category']['id']
                )), false);
        $this->assertEqual($this->Book->Category->delete($category['Category']['id']), true);
        $this->assertEqual($this->Book->Category->exists($category['Category']['id']), false);
    }

}
