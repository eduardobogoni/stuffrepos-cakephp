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
        $this->out('<info>Shell call: </info>' . $this->_shellCallToString($shellCall));
        $schedulingShellCallLog = Scheduling::findLog($shellCall);
        $this->out("  * Next run: " . (empty($schedulingShellCallLog['SchedulingShellCallLog']['next_run']) ? 'Not set' : $schedulingShellCallLog['SchedulingShellCallLog']['next_run']));
        $currentTimestamp = time();
        $this->out("  * Current: " . date('Y-m-d H:i:s', $currentTimestamp));
        $run = false;
        if ($schedulingShellCallLog['SchedulingShellCallLog']['next_run']) {
            if (strtotime($schedulingShellCallLog['SchedulingShellCallLog']['next_run']) <= $currentTimestamp) {
                $run = true;
                $this->_runShellCall($schedulingShellCallLog);
            }
        } else {
            Scheduling::setNextRun($schedulingShellCallLog['SchedulingShellCallLog']);
        }
        $this->out("  * Run: " . ($run ? 'Yes' : 'No'));
    }

    private function _runShellCall($schedulingShellCallLog) {
        $command = self::_shellCallCommand($schedulingShellCallLog);
        $this->out("  * Command: $command");
        exec($command);
    }

    private function _shellCallCommand($schedulingShellCallLog) {
        return $this->_consolePath() . ' Scheduling.run_shell_call ' .
                $schedulingShellCallLog['SchedulingShellCallLog']['id'] . ' &';
    }

    private function _consolePath() {
        return APP . 'Console' . DS . 'cake';
    }

    private function _shellCallToString($shellCall) {
        return trim($shellCall['scheduling'] . ' ' . $shellCall['shell'] . ' ' . Scheduling::serializeArgs($shellCall['args']));
    }

}
