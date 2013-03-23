<?php

class BaseArticleFixture extends CakeTestFixture {

    public $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'title' => array('type' => 'string', 'length' => 255, 'null' => false),
        'author_id' => array('type' => 'integer'),
    );
    public $records = array(
        array('id' => 1, 'title' => 'First Article', 'author_id' => 1),
        array('id' => 2, 'title' => 'Second Article', 'author_id' => 2),
        array('id' => 3, 'title' => 'Third Article', 'author_id' => 2),
        array('id' => 4, 'title' => 'Third Article', 'author_id' => null),
    );

}

class BaseArticle extends Model {

    public $belongsTo = array(
        'Author' => array(
            'className' => 'BaseAuthor',
            'foreignKey' => 'author_id',
        )
    );

}
