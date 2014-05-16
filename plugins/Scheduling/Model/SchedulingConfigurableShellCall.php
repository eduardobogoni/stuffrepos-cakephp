<?php

App::uses('AppModel', 'Model');
App::uses('ClassSearcher', 'Base.Lib');

/**
 * SchedulingConfigurableShellCall Model
 *
 */
class SchedulingConfigurableShellCall extends AppModel {

    public $actsAs = array(
        'Scheduling.CronValidation',
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
            'cronScheduling' => array(
                'rule' => array('cronScheduling'),
                'allowEmpty' => false,
            ),
        ),
        'shell' => array(
            'shellAvailable' => array(
                'rule' => array('shellAvailable'),
                'allowEmpty',
            ),
        ),
    );

    public function shells() {
        if (!isset($this->_shells)) {
            $this->_shells = array();
            foreach (ClassSearcher::findClasses('Console/Command') as $class => $path) {
                if ($class != 'AppShell') {
                    $this->_shells[] = (($dot = strpos($path, '.')) ?
                                    substr($path, 0, $dot) . '.' :
                                    '') . preg_replace('/_shell$/', '', Inflector::underscore($class));
                }
            }
            sort($this->_shells);
        }
        return $this->_shells;
    }

    public function shellAvailable($check) {
        foreach ($check as $value) {
            if (!in_array($value, $this->shells())) {
                return false;
            }
        }
        return true;
    }

}
