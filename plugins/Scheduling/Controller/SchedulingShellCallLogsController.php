<?php

App::uses('AppController', 'Controller');

/**
 * Balances Controller
 *
 */
class SchedulingShellCallLogsController extends AppController {

    /**
     * Scaffold
     *
     * @var mixed
     */
    public $scaffold;
    public $components = array(
        'ExtendedScaffold.ScaffoldUtil' => array(
            'indexSetFields' => array(
                'scheduling',
                'shell',
                'next_run',
                'args',
                'next_run',
                'state',
            ),
        )
    );

}
