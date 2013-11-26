<?php

interface MakeListener {

    public function onMakeBeforeExecute($taskName);

    public function onMakeAfterExecute($taskName);

    public function onMakeAfterCheck($taskName, $result, $returnedValue, $expectedValue);
}