<?php

App::uses('Scheduling', 'Scheduling.Lib');

class SchedulingUpdateShell extends Shell {

    public function main() {
        $this->out('<info>Atualizando arquivo cron...</info>');
        Scheduling::update();
        $cronFile = Configure::read('install.cron_tasks_file');
        $this->out("<info>Atualizado. Conteúdo de \"$cronFile\"</info>");
        $this->out(file_get_contents($cronFile));
    }

}
