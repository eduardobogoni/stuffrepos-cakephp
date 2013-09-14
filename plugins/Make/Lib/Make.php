<?php

class Make {

    private $tasks = array();
    private $afterExecute = false;
    private $beforeExecute = false;
    private $afterCheck = false;

    /**
     * 
     * @param string $taskName
     * @param array $dependencies
     * @param callback $checkFunction
     * @param callback $executeFunction
     * @throws ErrorException
     */
    public function addTask($taskName, $dependencies, $checkFunction, $executeFunction, $expectedValue) {
        if (!is_array($dependencies)) {
            throw new InvalidArgumentException("Parameter 'dependencies' is not a array");
        }

        foreach ($dependencies as $key => $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException("Dependency $key of task \"$taskName\" is not a string");
            }
        }

        $this->tasks[$taskName] = compact('dependencies', 'checkFunction', 'executeFunction', 'expectedValue');
    }

    public function tasks() {
        return array_keys($this->tasks);
    }

    public function execute($name) {
        $this->_execute($name, array(), array());
    }

    public function setAfterExecute($callback) {
        $this->afterExecute = $callback;
    }

    public function setBeforeExecute($callback) {
        $this->beforeExecute = $callback;
    }

    public function setAfterCheck($callback) {
        $this->afterCheck = $callback;
    }

    private function _execute($taskName, $executedTasks, $stack) {
        if (!array_key_exists($taskName, $this->tasks)) {
            throw new Exception("Task \"$taskName\" do not exists");
        }

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
    
    public function check($taskName, &$returnedValue = null, &$expectedValue = null) {
        $returnedValue = call_user_func($this->tasks[$taskName]['checkFunction']);
        $expectedValue = $this->tasks[$taskName]['expectedValue'];
        return $returnedValue == $expectedValue;
    }

    private function _runTask($taskName) {
        $checkResult = $this->check($taskName, $returnedValue, $expectedValue);

        if ($this->afterCheck) {
            call_user_func($this->afterCheck, $taskName, $checkResult
                    , $returnedValue, $this->tasks[$taskName]['expectedValue']);
        }

        if (!$checkResult) {
            if ($this->beforeExecute) {
                call_user_func($this->beforeExecute, $taskName);
            }
            call_user_func($this->tasks[$taskName]['executeFunction']);

            if (!$this->check($taskName, $returnedValue, $expectedValue)) {
                throw new Exception("Task executed, but check returned false: " . print_r(compact('returnedValue', 'expectedValue')));
            }

            if ($this->afterExecute) {
                call_user_func($this->afterExecute, $taskName);
            }
        }
    }

}