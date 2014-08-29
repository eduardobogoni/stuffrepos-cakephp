<?php

App::uses('MakeListener', 'Make.Lib');
App::uses('TasksObject', 'Make.Lib');

class Make {

    /**
     *
     * @var array() 
     */
    private $tasks = array();

    /**
     *
     * @var MakeListener[]
     */
    private $listeners = array();

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

        if (!is_callable($checkFunction)) {
            throw new InvalidArgumentException("\"$taskName\" check function is not a valid callback. " . print_r($checkFunction, true));
        }

        if (!is_callable($executeFunction)) {
            throw new InvalidArgumentException("\"$taskName\" execute function is not a valid callback. " . print_r($executeFunction, true));
        }

        $this->tasks[$taskName] = compact('dependencies', 'checkFunction', 'executeFunction', 'expectedValue');
    }

    public function tasks() {
        return array_keys($this->tasks);
    }

    public function execute($name) {
        $this->_execute($name, array(), array());
        $this->_fireListeners('onMakeAfterExecuteAll', array());
    }

    public function addListener(MakeListener $listener) {
        $this->listeners[] = $listener;
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

    public function checkRecursive($name) {     
        return $this->_checkRecursive($name, array(), array()) !== false;
    }
    
    private function _checkRecursive($taskName, $checkedTasks, $stack) {
        if (!array_key_exists($taskName, $this->tasks)) {
            throw new Exception("Task \"$taskName\" do not exists");
        }

        if (!in_array($taskName, $checkedTasks)) {
            if (in_array($taskName, $stack)) {
                throw new Exception("Circular reference: " . print_r(compact('taskName', 'stack'), true));
            }

            $stack[] = $taskName;
            foreach ($this->tasks[$taskName]['dependencies'] as $dependency) {
                $subCheckedTasks = $this->_checkRecursive($dependency, $checkedTasks, $stack);
                if ($subCheckedTasks === false) {
                    return false;
                }
                $checkedTasks += $subCheckedTasks;
            }
            if ($this->check($taskName)) {
                $checkedTasks[] = $taskName;
            }
            else {
                return false;
            }
            
            array_pop($stack);
        }

        return $checkedTasks;
    }

    public function addTasksObject(TasksObject $tasksObject) {
        foreach ($tasksObject->getTasksConfiguration() as $taskName => $taskData) {
            $this->_addTasksObjectTask($tasksObject, $taskName, $taskData);
        }
    }

    private function _addTasksObjectTask(TasksObject $tasksObject, $taskName, $taskData) {
        $defaultExecuteFunction = function() {
                    return false;
                };
        $checkFunction = null;
        $executeFunction = $this->_tasksObjectMethod(
                $tasksObject
                , '_' . Inflector::variable($taskName)
                , $defaultExecuteFunction
        );
        $dependencies = array();
        $expectedValue = true;
        foreach ($taskData as $key => $value) {
            if ($key === 'executeFunction') {
                $executeFunction = $this->_tasksObjectCustomFunction($tasksObject, $value);
            } else if ($key === 'checkFunction') {
                $checkFunction = $this->_tasksObjectCustomFunction($tasksObject, $value);
            } else if ($key === 'expectedValue') {
                $expectedValue = $value;
            } else {
                $dependencies[] = $value;
            }
        }
        if (!$checkFunction) {
            $checkFunctionName = '_' . Inflector::variable('check_' . $taskName);
            $_this = $this;
            $checkFunction = method_exists($tasksObject, $checkFunctionName) ?
                    array($tasksObject, $checkFunctionName) : function() use ($dependencies, $_this) {
                        return true;
                        foreach ($dependencies as $dependency) {
                            if (!$_this->checkRecursive($dependency)) {
                                return false;
                            }
                        }

                        return true;
                    };
        }

        $this->addTask(
                $taskName
                , $dependencies
                , $checkFunction
                , $executeFunction
                , $expectedValue
        );
    }

    private function _tasksObjectCustomFunction(TasksObject $tasksObject, $value) {
        $params = ArrayUtil::arraylize($value);
        $functionName = $value[0];
        array_shift($params);
        return function() use ($tasksObject, $functionName, $params) {
                    return call_user_func_array(array($tasksObject, $functionName), $params);
                };
    }

    private function _tasksObjectMethod(TasksObject $tasksObject, $methodName, $defaultFunction) {
        if (method_exists($tasksObject, $methodName)) {
            $reflection = new ReflectionMethod($tasksObject, $methodName);
            if (!$reflection->isPublic()) {
                throw new RuntimeException("Método \"$methodName\" não é público.");
            }
            return array($tasksObject, $methodName);
        } else {
            return $defaultFunction;
        }
    }

    private function _runTask($taskName) {
        $checkResult = $this->check($taskName, $returnedValue, $expectedValue);

        $this->_fireListeners('onMakeAfterCheck', array(
            $taskName
            , $checkResult
            , $returnedValue
            , $this->tasks[$taskName]['expectedValue']
        ));

        if (!$checkResult) {
            $this->_fireListeners('onMakeBeforeExecute', array($taskName));
            call_user_func($this->tasks[$taskName]['executeFunction']);

            if (!$this->check($taskName, $returnedValue, $expectedValue)) {
                throw new Exception("Task executed, but check returned false: " . print_r(compact('returnedValue', 'expectedValue'), true));
            }

            $this->_fireListeners('onMakeAfterExecute', array($taskName));
        }
    }

    private function _fireListeners($method, $parameters) {
        foreach ($this->listeners as $listener) {
            call_user_method_array($method, $listener, $parameters);
        }
    }

}