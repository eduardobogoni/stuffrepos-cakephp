<?php

App::uses('Scheduling', 'Scheduling.Lib');

class CheckAndRunShell extends Shell {

    public $uses = array(
        'Scheduling.SchedulingShellCallLog',
    );

    public function main() {
        foreach (Scheduling::shellCalls() as $shellCall) {
            $this->_checkShellCall($shellCall);
        }
    }

    private function _checkShellCall($shellCall) {
        $schedulingShellCallLog = $this->_getShellCallLog($shellCall);
        if ($schedulingShellCallLog['SchedulingShellCallLog']['next_run'] || strtotime($schedulingShellCallLog['SchedulingShellCallLog']['next_run']) <= time()) {
            $this->_runShellCall($schedulingShellCallLog);
        }
    }

    private function _runShellCall($schedulingShellCallLog) {
        exec(self::_shellCallCommand($schedulingShellCallLog));
        
    }

    private function _shellCallCommand($schedulingShellCallLog) {
        return $this->_consolePath() . ' Scheduling.run_shell_call ' .
                $schedulingShellCallLog['SchedulingShellCallLog']['id'] . ' &';
    }

    private function _consolePath() {
        return APP . DS . 'Console' . DS . 'cake';
    }

    private function _getShellCallLog($shellCall) {
        $schedulingShellCallLog = $this->SchedulingShellCallLog->find('first', array(
            'conditions' => array(
                'SchedulingShellCallLog.scheduling' => $shellCall['scheduling'],
                'SchedulingShellCallLog.args' => $shellCall['args'],
                'SchedulingShellCallLog.shell' => $shellCall['shell'],
            )
        ));
        if (empty($schedulingShellCallLog)) {
            $this->SchedulingShellCallLog->create();
            $this->SchedulingShellCallLog->saveOrThrowException(array(
                'SchedulingShellCallLog' => $shellCall
            ));
            return $this->SchedulingShellCallLog->read();
        } else {
            return $schedulingShellCallLog;
        }
    }

}
