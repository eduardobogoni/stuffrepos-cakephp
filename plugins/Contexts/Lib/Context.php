<?php

App::uses('Basics', 'Base.Lib');

class Context {

    public function getInstanceIdByUrl($url) {
        return $this->_internalGetInstanceIdByUrl(
                        Basics::parseUrl($url));
    }

    protected function _internalGetInstanceIdByUrl($url) {
        return $url[0];
    }

    public function buildUrl($url, $contextInstanceId) {
        return $this->_internalBuildUrl(
                        Basics::parseUrl($url), $contextInstanceId);
    }

    protected function _internalBuildUrl($url, $contextInstanceId) {
        $url[0] = $contextInstanceId;
        return $url;
    }

}
