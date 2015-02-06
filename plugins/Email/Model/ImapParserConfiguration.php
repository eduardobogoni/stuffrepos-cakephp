<?php

App::uses('AppModel', 'Model');

/**
 * ImapParserConfiguration Model
 *
 */
class ImapParserConfiguration extends AppModel {

    public $actsAs = array(
        'Base.ExtendedOperations',
    );

    /**
     * Display field
     *
     * @var string
     */
    public $displayField = 'parser';

    /**
     * Validation rules
     *
     * @var array
     */
    public $validate = array(
        'parser' => array(
            'inList' => array(
                'rule' => array('inList', ['to', 'replace', 'in', 'constructor']),
                'allowEmpty' => false,
            ),
            'isUnique' => array(
                'rule' => array('isUnique'),
            ),
        ),
        'enabled' => array(
            'boolean' => array(
                'rule' => array('boolean'),
            ),
        ),
    );

    public function __construct($id = false, $table = null, $ds = null) {
        $this->validate['parser']['inList']['rule'][1] = array_keys(ClassSearcher::findClasses('Lib/EmailParser'));
        sort($this->validate['parser']['inList']['rule'][1]);
        parent::__construct($id, $table, $ds);
    }

}
