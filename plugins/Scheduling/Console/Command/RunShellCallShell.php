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
        App::uses('IncludePath', 'Base.Lib');
        IncludePath::initAutoload();
        IncludePath::addPath(CakePlugin::path('Scheduling') . 'Vendor' . DS . 'CronExpression' . DS . 'src');
        $schedulingShellCallLog = $this->SchedulingShellCallLog->findByIdOrThrowException(
                $this->args[0]
        );
        $this->_runShellCall($schedulingShellCallLog);
        $this->_updateLog($schedulingShellCallLog);
    }

    private function _runShellCall($schedulingShellCallLog) {
        $this->dispatchShell(
                $schedulingShellCallLog['SchedulingShellCallLog']['shell'] .
                ' ' . $schedulingShellCallLog['SchedulingShellCallLog']['args']
        );
    }

    private function _updateLog($schedulingShellCallLog) {
        $cron = Cron\CronExpression::factory($schedulingShellCallLog['SchedulingShellCallLog']['scheduling']);
        $schedulingShellCallLog['SchedulingShellCallLog']['next_run'] = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        $this->SchedulingShellCallLog->saveOrThrowException($schedulingShellCallLog);
    }

}
