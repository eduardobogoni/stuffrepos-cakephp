<?php

App::uses('ClassSearcher', 'Base.Lib');
App::uses('Scheduling', 'Scheduling.Lib');

class InstallShell extends Shell {

    public function getOptionParser() {
        return parent::getOptionParser()->addOption(
                        'installer-class', array(
                    'default' => '',
                        )
        );
    }

    public function main() {
        $this->out('<info>Searching scheduling installer...</info>');
        $manager = Scheduling::getInstaller($this->params['installer-class']);
        $this->out('Installer found: '.  get_class($manager));
        $this->out('<info>Installing...</info>');
        $manager->install();
        $this->out('<info>Done</info>');
    }
    
    public function uninstall() {
        $this->out('<info>Searching scheduling installer...</info>');
        $manager = Scheduling::getInstaller($this->params['installer-class']);
        $this->out('Installer found: '.  get_class($manager));
        $this->out('<info>Uninstalling...</info>');
        $manager->uninstall();
        $this->out('<info>Done</info>');
    }

}
