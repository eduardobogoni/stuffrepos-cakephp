<?php

App::uses('Scheduling', 'Scheduling.Lib');

class CheckAndRunShell extends Shell {

    const SCHEDULING_STATE_RUN = 'run';
    const SCHEDULING_STATE_PASS = 'pass';
    const SCHEDULING_STATE_SET_NEXT_RUN = 'set_next_run';
    const SCHEDULING_STATE_TIMEOUT = 'timeout';
    const PROCESS_TIMEOUT = 3600;

    public $uses = array(
        'Scheduling.SchedulingShellCallLog',
    );

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->addOption('ignore-scheduling', array(
            'boolean' => true
        ));
        return $parser;
    }

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
        switch ($this->_schedulingState($schedulingShellCallLog, $currentTimestamp)) {
            case self::SCHEDULING_STATE_RUN:
                $this->_runShellCall($schedulingShellCallLog);
                break;

            case self::SCHEDULING_STATE_TIMEOUT:
                $this->_killProcess($schedulingShellCallLog);
                $this->_runShellCall($schedulingShellCallLog);
                break;

            case self::SCHEDULING_STATE_SET_NEXT_RUN:
                $this->out('  * Setting next run...');
                Scheduling::setNextRun($schedulingShellCallLog['SchedulingShellCallLog']);
                break;

            case self::SCHEDULING_STATE_PASS:
                $this->out('  * Pass');
                break;
        }
        $this->out('  * Done');
    }

    private function _schedulingState($schedulingShellCallLog, $currentTimestamp) {
        if (!$schedulingShellCallLog['SchedulingShellCallLog']['next_run']) {
            return self::SCHEDULING_STATE_SET_NEXT_RUN;
        }
        $nextRun = strtotime($schedulingShellCallLog['SchedulingShellCallLog']['next_run']);
        if ($nextRun <= $currentTimestamp || $this->params['ignore-scheduling']) {
            $currentPid = $schedulingShellCallLog['SchedulingShellCallLog']['current_pid'];
            if ($currentPid) {
                $processAge = $this->_currentProcessLifeTime($currentPid);
                if ($processAge === false) {
                    return self::SCHEDULING_STATE_RUN;
                } else if ($processAge > self::PROCESS_TIMEOUT) {
                    return self::SCHEDULING_STATE_TIMEOUT;
                } else {
                    return self::SCHEDULING_STATE_PASS;
                }
            }
            return self::SCHEDULING_STATE_RUN;
        }
        return self::SCHEDULING_STATE_PASS;
    }

    private function _runShellCall($schedulingShellCallLog) {
        $this->out('  * Running...');
        $command = self::_shellCallCommand($schedulingShellCallLog);
        $this->out("  * Command: $command");
        $pid = $this->_execInBackground($command);
        $this->out("  * PID: $pid");
        $this->out("  * Storing process id...");
        $this->_storeProcessId($schedulingShellCallLog, $pid);
    }

    private function _storeProcessId($schedulingShellCallLog, $pid) {
        ClassRegistry::init('SchedulingShellCallLog')->saveOrThrowException(array(
            'SchedulingShellCallLog' => array(
                'id' => $schedulingShellCallLog['SchedulingShellCallLog']['id'],
                'current_pid' => $pid
            )
                )
        );
    }

    private function _currentProcessLifeTime($pid) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            return $this->_currentProcessLifeTimeWindows($pid);
        } else {
            return $this->_currentProcessLifeTimeDefault($pid);
        }
    }

    private function _currentProcessLifeTimeDefault($pid) {
        exec("ps -p $pid -o lstart=", $output);
        if (empty($output)) {
            return false;
        } else {
            return time() - strtotime($output[0]);
        }
    }

    private function _killProcess($schedulingShellCallLog) {
        $this->out('  * Killing process ' . $schedulingShellCallLog['SchedulingShellCallLog']['current_pid'] . '...');
        system('kill -9 ' . $schedulingShellCallLog['SchedulingShellCallLog']['current_pid']);
    }

    private function _execInBackground($command) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            return $this->_execInBackgroundWindows($command);
        } else {
            return $this->_execInBackgroundDefault($command);
        }
    }

    private function _execInBackgroundDefault($command) {
        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("file", "/dev/null", "a"), // stdout is a pipe that the child will write to
            2 => array("file", "/dev/null", "a") // stderr is a file to write to
        );
        $pipes = array();
        $process = proc_open($command, $descriptorspec, $pipes);
        if ($process) {
            $info = proc_get_status($process);
            return $info['pid'];
        } else {
            throw new Exception("Process was not created");
        }
    }

    private function _execInBackgroundWindows($command) {
        throw new Exception("Not yet implemented: " . __METHOD__);
        //pclose(popen("start /B " . $command, "r"));
    }

    private function _shellCallCommand($schedulingShellCallLog) {
        return $this->_consolePath() . ' Scheduling.run_shell_call ' .
                $schedulingShellCallLog['SchedulingShellCallLog']['id'];
    }

    private function _consolePath() {
        return APP . 'Console' . DS . 'cake';
    }

    private function _shellCallToString($shellCall) {
        return trim($shellCall['scheduling'] . ' ' . $shellCall['shell'] . ' ' . Scheduling::serializeArgs($shellCall['args']));
    }

}
