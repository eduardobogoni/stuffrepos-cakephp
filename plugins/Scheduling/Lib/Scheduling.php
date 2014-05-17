<?php

App::uses('ClassSearcher', 'Base.Lib');
App::uses('SchedulingTask', 'Scheduling.Lib');

/**
 * 
 * interface SchedulingManager {
 *   public function update($shellCalls);
 * }
 * 
 * interface SchedulingTask {  
 * @return array('scheduling' => string, 'shell' => string, args => string[])[]
 *   public function generate();
 * }
 */
class Scheduling {

    /**
     * 
     * @return array('scheduling' => string, 'shell' => string, args => string[])[]
     */
    public static function shellCalls() {
        $shellCalls = array();
        foreach (self::_findSchedulingTasksInstances() as $schedulingTask) {
            $shellCalls = array_merge(
                    $shellCalls
                    , self::_generateShellCalls($schedulingTask)
            );
        }
        return $shellCalls;
    }

    public static function setNextRun($shellCall) {
        $schedulingShellCallLog = self::findLog($shellCall);
        $cron = Cron\CronExpression::factory($shellCall['scheduling']);
        $schedulingShellCallLog['SchedulingShellCallLog']['next_run'] = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        unset($schedulingShellCallLog['SchedulingShellCallLog']['modified']);
        ClassRegistry::init('Scheduling.SchedulingShellCallLog')->saveOrThrowException($schedulingShellCallLog);
    }

    public static function findLog($shellCall) {
        $SchedulingShellCallLog = ClassRegistry::init('Scheduling.SchedulingShellCallLog');
        $schedulingShellCallLog = $SchedulingShellCallLog->find('first', array(
            'conditions' => array(
                'SchedulingShellCallLog.scheduling' => $shellCall['scheduling'],
                'SchedulingShellCallLog.args' => self::serializeArgs($shellCall['args']),
                'SchedulingShellCallLog.shell' => $shellCall['shell'],
            )
        ));
        if (empty($schedulingShellCallLog)) {
            $SchedulingShellCallLog->create();
            $shellCall['args'] = self::serializeArgs($shellCall['args']);
            $SchedulingShellCallLog->saveOrThrowException(array(
                'SchedulingShellCallLog' => $shellCall
            ));
            return $SchedulingShellCallLog->read();
        } else {
            return $schedulingShellCallLog;
        }
    }

    public static function serializeArgs($args) {
        if (is_array($args)) {
            return trim(implode(' ', array_map('escapeshellarg', $args)));
        } else {
            return trim('' . $args);
        }
    }
    
    private static function _generateShellCalls($schedulingTask) {
        $shellCalls = array();
        foreach($schedulingTask->generate() as $shellCall) {
            if (empty($shellCall['args'])) {
                $shellCall['args'] = array();
            }
            $shellCalls[] = $shellCall;
        }
        return $shellCalls;
    }

    /**
     * @return SchedulingTask[]
     */
    private static function _findSchedulingTasksInstances() {
        return ClassSearcher::findInstances('Lib' . DS . 'SchedulingTask');
    }

}