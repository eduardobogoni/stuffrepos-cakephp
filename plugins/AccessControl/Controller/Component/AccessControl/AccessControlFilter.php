<?php

App::uses('CakeRequest', 'Network');

interface AccessControlFilter {

    public function userHasAccess(CakeRequest $request, $user, $object, $objectType);
}
