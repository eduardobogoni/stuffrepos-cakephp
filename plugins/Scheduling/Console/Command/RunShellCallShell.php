<?php

App::uses('Scheduling', 'Scheduling.Lib');

class RunShellCallShell extends Shell {

    public $uses = array(
        'Scheduling.SchedulingShellCallLog',
    );

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->addArgument('scheduling_shell_call_log_id', array(
            'required' => true,
        ));
        return $parser;
    }

    public function main() {
        $schedulingShellCallLog = $this->SchedulingShellCallLog->findByIdOrThrowException(
                $this->args[0]
        );
        $this->_runShellCall($schedulingShellCallLog);
        Scheduling::setNextRun($schedulingShellCallLog['SchedulingShellCallLog']);
    }

    private function _runShellCall($schedulingShellCallLog) {
        $this->dispatchShell(
                $schedulingShellCallLog['SchedulingShellCallLog']['shell'] .
                ' ' . $schedulingShellCallLog['SchedulingShellCallLog']['args']
        );
    }

}
