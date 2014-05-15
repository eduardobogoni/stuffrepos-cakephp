<?php

App::uses('FileSystem', 'Base.Lib');
App::uses('SchedulingTask', 'Scheduling.Lib');

class CronSchedulingManager {

    public function update() {
        $this->_setCrontabUserContents($this->_cronFileContent());
    }

    private function _getCrontabUserContents() {
        @exec('crontab -l', $lines, $result);
        if ($result == 0) {
            return implode("\n", $lines);
        } else {
            return '';
        }
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
    private function _cronFileContent() {
        $lines = array();
        $appCronLineFound = false;
        foreach (explode("\n", $this->_getCrontabUserContents()) as $line) {
            if (trim($line) != '' && !$this->_isAppCronLine($line)) {
                $lines[] = $line;
            }
        }
        $lines[] = $this->_buildAppCronLine();
        return implode("\n", $lines);
    }

    private function _isAppCronLine($line) {
        return preg_match('/\#\s*APP_ID\:\s*' . preg_quote(APP_ID) . '\s*$/', $line);
    }

    private function _buildAppCronLine() {
        return '*/1 * * * * ' . $this->_quoteCommand(array(
                    APP . DS . 'Console' . DS . 'cake',
                    'Scheduling.check_and_run',
                )) . '# APP_ID: ' . APP_ID . "\n";
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
        return $b;
    }

}