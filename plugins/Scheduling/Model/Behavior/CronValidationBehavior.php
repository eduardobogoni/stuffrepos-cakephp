<?php

App::uses('Scheduling', 'Scheduling.Lib');

class CronValidationBehavior extends ModelBehavior {

    public function cronScheduling(\Model $model, $check) {
        foreach ($check as $value) {
            try {
                \Cron\CronExpression::factory($value);
            } catch (InvalidArgumentException $ex) {
                return false;
            }
        }
        return true;
    }

}
