<?php

App::uses('ClassSearcher', 'Base.Lib');

class InstallShell extends Shell {

    public function main() {
        $this->out('<info>Searching scheduling installer...</info>');
        $manager = Scheduling::getInstaller();
        $this->out('Installer found: '.  get_class($manager));
        $this->out('<info>Installing...</info>');
        $manager->install();
        $this->out('<info>Done</info>');
    }
    
    public function uninstall() {
        $this->out('<info>Searching scheduling installer...</info>');
        $manager = Scheduling::getInstaller();
        $this->out('Installer found: '.  get_class($manager));
        $this->out('<info>Uninstalling...</info>');
        $manager->uninstall();
        $this->out('<info>Done</info>');
    }

}
