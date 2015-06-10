<?php

App::uses('Scheduling', 'Scheduling.Lib');

class ListShell extends Shell {

    public function main() {
        foreach (Scheduling::shellCalls() as $shellCall) {            
            
            $this->out("<info>|{$shellCall['id']}| {$shellCall['scheduling']} {$this->__command($shellCall)} </info>");
            $log = Scheduling::findLog($shellCall);
            if (empty($log)) {
                $this->out("\tLog indisponível");
            }
            else {
                $fields = ['id','next_run', 'current_pid', 'state'];                
                $values = [];
                foreach($fields as $field) {
                    $values[] = $field.': '.$log['SchedulingShellCallLog'][$field];
                }
                $this->out("\t".implode(', ', $values));
            }
        }
    }
    
    private function __command($shellCall) {
        $args = is_array($shellCall['args']) ? implode(' ',$shellCall['args']) : $shellCall['args'];
        return trim($shellCall['shell'].' '.$args);
    }


}
