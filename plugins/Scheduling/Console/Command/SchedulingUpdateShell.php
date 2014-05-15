<?php

App::uses('Scheduling', 'Scheduling.Lib');

class SchedulingUpdateShell extends Shell {

    public function main() {
        $this->out('<info>Atualizando arquivo cron...</info>');
        Scheduling::update();
        $this->out('<info>Atualizando!</info>');
    }

}
