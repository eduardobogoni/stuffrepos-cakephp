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