<?php

class JournalDetail extends AppModel {

    public $validate = array(
        'property' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
            ),
        ),
        'journal_id' => array(
            'numeric' => array(
                'rule' => 'numeric',
                'required' => true,
            ),
        ),
    );

}
