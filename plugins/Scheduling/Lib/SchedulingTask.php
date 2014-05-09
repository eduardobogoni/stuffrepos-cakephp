<?php

interface SchedulingTask {
    
    /**
     * @return array('scheduling' => string, 'shell' => string, args => string[])[]
     */
    public function generate();
    
}

