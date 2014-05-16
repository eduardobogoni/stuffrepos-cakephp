<?php

class ConfigurableShellCallsSchedulingTask {

    public function generate() {
        return array_map(function($configurableShellCall) {
            return $configurableShellCall['SchedulingConfigurableShellCall'];
        }, ClassRegistry::init('Scheduling.SchedulingConfigurableShellCall')->find('all'));
    }

}
