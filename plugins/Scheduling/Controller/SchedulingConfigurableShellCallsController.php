<?php

App::uses('AppController', 'Controller');

/**
 * SchedulingConfigurableShellCalls Controller
 *
 */
class SchedulingConfigurableShellCallsController extends AppController {

    /**
     * Scaffold
     *
     * @var mixed
     */
    public $scaffold;
    
    public function beforeRender() {
        parent::beforeRender();
        $this->set('shells', ArrayUtil::keysAsValues($this->SchedulingConfigurableShellCall->shells()));
    }

}
