<?php

class Make {

    private $tasks = array();

    public function addTask($taskName, $dependencies, $checkFunction, $executeFunction) {
        $this->tasks[$taskName] = compact('dependencies', 'checkFunction', 'executeFunction');
    }

    public function execute($name) {
        $this->_execute($name, array(), array());
    }

    private function _execute($taskName, $executedTasks, $stack) {
        if (!in_array($taskName, $executedTasks)) {
            if (in_array($taskName, $stack)) {
                throw new Exception("Circular reference: " . print_r(compact('taskName', 'stack'), true));
            }

            $stack[] = $taskName;
            foreach ($this->tasks[$taskName]['dependencies'] as $dependency) {
                $executedTasks += $this->_execute($dependency, $executedTasks, $stack);
            }
            $this->_runTask($taskName);
            $executedTasks[] = $taskName;
            array_pop($stack);
        }

        return $executedTasks;
    }

    private function _runTask($taskName) {
        if (!call_user_func($this->tasks[$taskName]['checkFunction'])) {
            call_user_func($this->tasks[$taskName]['executeFunction']);            
        }
    }

}