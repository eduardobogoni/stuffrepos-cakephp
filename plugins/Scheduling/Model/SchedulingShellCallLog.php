<?php

App::uses('AppModel', 'Model');

/**
 * SchedulingTask Model
 *
 */
class SchedulingShellCallLog extends AppModel {
    
    public $actsAs = array(
        'Base.ExtendedOperations',
    );

    /**
     * Display field
     *
     * @var string
     */
    public $displayField = 'shell';

    /**
     * Validation rules
     *
     * @var array
     */
    public $validate = array(
        'scheduling' => array(
            'notEmpty' => array(
                'rule' => array('notEmpty'),
            ),
        ),
        'shell' => array(
            'notEmpty' => array(
                'rule' => array('notEmpty'),
            ),
        ),
    );

}
