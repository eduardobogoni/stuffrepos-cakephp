<?php

App::uses('ClassSearcher', 'Base.Lib');

class InstallShell extends Shell {

    public function main() {
        $this->out('<info>Searching scheduling installer...</info>');
        $manager = $this->_getManager();
        $this->out('Installer found: '.  get_class($manager));
        $this->out('<info>Installing...</info>');
        $manager->update();
        $this->out('<info>Done</info>');
    }

    /**
     * @return \SchedulingManager
     */
    private function _getManager() {
        $targetClass = Configure::read('Scheduling.manager_class');
        if (!$targetClass || trim($targetClass) == '') {
            throw new Exception("Configuration \"Scheduling.manager_class\" not set.");
        }
        return ClassSearcher::findInstanceAndInstantiate('Lib' . DS . 'SchedulingManager', $targetClass);
    }

}
