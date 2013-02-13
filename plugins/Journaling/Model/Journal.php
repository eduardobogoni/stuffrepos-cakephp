<?php

class Journal extends AppModel {

    public $validate = array(
        'type' => array(
            'inList' => array(
                'rule' => array('inList', array('create', 'update', 'delete'))  ,
                'required' => true,
            )
        ),
        'journalized_type' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
            ),
        ),
        'journalized_id' => array(
            'numeric' => array(
                'rule' => 'numeric',
                'required' => true,
            ),
        ),
    );

    /**
     * hasMany associations
     *
     * @var array
     */
    public $hasMany = array(
        'JournalDetail' => array(
            'className' => 'JournalDetail',
            'dependent' => true,
        )
    );

}
