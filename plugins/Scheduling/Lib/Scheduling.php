<?php

App::uses('SchedulingTask', 'Scheduling.Lib');

class Scheduling {

    public static function update() {
        $apache = UbuntuEnv::init(true, Configure::read('install.apache_user'));
        $root = new UbuntuEnv(true, false, $apache);
        $root->filePutContents(
                Configure::read('install.cron_tasks_file')
                , self::_cronFileContent()
        );
    }

    /**
     * 
     * @return string
     */
    private static function _cronFileContent() {
        $content = '';
        foreach (self::_cronShellsCalls() as $shellCall) {
            $content .= self::_cronLine($shellCall);
        }
        return $content;
    }

    /**
     * 
     * @param array('scheduling' => string, 'shell' => string, args => string[]) $shellCall
     * @return string
     */
    private static function _cronLine($shellCall) {
        return $shellCall['scheduling']
            . ' ' . self::_cronLineUser($shellCall)
            . ' ' . self::_quoteCommand(array_merge(
                array(
                    ROOT . DS . 'cake.sh',
                    $shellCall['shell'],
                ), $shellCall['args']
        ));
    }
    
    private function _cronLineUser($shellCall) {
        return empty($shellCall['user']) ?
                Configure::read('install.apache_user') :
                $shellCall['user'];
    }

    /**
     * 
     * @param array $args
     * @return string
     */
    private static function _quoteCommand($args) {
        $b = '';
        foreach ($args as $arg) {
            $b .= escapeshellarg($arg) . ' ';
        }
        return $b . "\n";
    }

    /**
     * 
     * @return array('scheduling' => string, 'shell' => string, args => string[])[]
     */
    private static function _cronShellsCalls() {
        $shellCalls = array();
        foreach (self::_findCronTasksInstances() as $cronTask) {
            $shellCalls = array_merge(
                    $shellCalls
                    , $cronTask->generate()
            );
        }
        return $shellCalls;
    }

    /**
     * @return SchedulingTask[]
     */
    private static function _findCronTasksInstances() {
        App::build(array(
            'SchedulingTask' => array('%s' . self::_cronTasksPath())
                ), App::REGISTER);
        $cronTasks = array();
        foreach (App::objects('SchedulingTask') as $cronTaskName) {
            App::uses($cronTaskName, self::_cronTasksPath());
            $cronTask = new $cronTaskName();
            if ($cronTask instanceof SchedulingTask) {
                $cronTasks[] = new $cronTaskName();
            } else {
                throw new Exception("Classe \"$cronTask\" não implementa \"SchedulingTask\"");
            }
        }
        return $cronTasks;
    }

    /**
     * Caminho do diretório de classes CronTask.
     * @return string
     */
    private static function _cronTasksPath() {
        return 'Lib' . DS . 'SchedulingTasks';
    }

}