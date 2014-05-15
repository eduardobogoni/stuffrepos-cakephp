<?php

App::uses('FileSystem', 'Base.Lib');
App::uses('SchedulingTask', 'Scheduling.Lib');

class CronSchedulingManager {

    public function update($shellCalls) {
        $this->_setCrontabUserContents($this->_cronFileContent($shellCalls));
    }

    private function _getCrontabUserContents() {
        $command = 'crontab -l';
        @exec($command, $lines, $result);
        if ($result !== 0) {
            throw new Exception("\"$command\" returned \"$result\"");
        }
        return implode("\n", $lines);
    }

    private function _setCrontabUserContents($contents) {
        $tmpFile = FileSystem::createTemporaryFile();
        file_put_contents($tmpFile, $contents);
        $command = 'crontab ' . escapeshellarg($tmpFile);
        @exec($command, $lines, $result);
        if ($result !== 0) {
            throw new Exception("\"$command\" returned \"$result\"");
        }
    }

    /**
     * 
     * @return string
     */
    private function _cronFileContent($shellCalls) {
        $content = '';
        foreach ($shellCalls as $shellCall) {
            $content .= self::_cronLine($shellCall);
        }
        return $content;
    }

    /**
     * 
     * @param array('scheduling' => string, 'shell' => string, args => string[]) $shellCall
     * @return string
     */
    private function _cronLine($shellCall) {
        return $shellCall['scheduling']
                . ' ' . self::_cronLineUser($shellCall)
                . ' ' . self::_quoteCommand(array_merge(
                                array(
                    APP . 'Console' . DS . 'cake',
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
    private function _quoteCommand($args) {
        $b = '';
        foreach ($args as $arg) {
            $b .= escapeshellarg($arg) . ' ';
        }
        return $b . "\n";
    }

}