<?php

interface AccessControlFilter {

    public function userHasAccessByUrl($user, $url);
}
