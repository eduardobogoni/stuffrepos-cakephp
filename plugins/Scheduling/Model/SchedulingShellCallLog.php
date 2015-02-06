<?php

App::uses('AppModel', 'Model');

/**
 * SchedulingTask Model
 *
 */
class SchedulingShellCallLog extends AppModel {
    
    const STATE_RUNINNG = 'RUNNING';
    const STATE_WAITING = 'WAITING';

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

    public function getVirtualFields() {
        return array(
            'state' => "'1'",
        );
    }

    public function afterFind($results, $primary = false) {
        $results = parent::afterFind($results, $primary);
        foreach (array_keys($results) as $k) {
            if (!empty($results[$k][$this->alias])) {
                $results[$k][$this->alias]['state'] = $this->__getState($results[$k]);
            }
        }
        return $results;
    }

    private function __getState($row) {
        return $this->__processRunning($row[$this->alias]['current_pid']) ?
                self::STATE_RUNINNG :
                self::STATE_WAITING;
    }

    private function __processRunning($pid) {
        return file_exists("/proc/$pid");
    }

}
