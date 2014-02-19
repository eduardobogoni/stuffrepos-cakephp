<?php

class Context {

    public function getInstanceIdByUrl($url) {
        if (!is_array($url)) {
            $url = Router::parse($url);
        }
        return $this->_internalGetInstanceIdByUrl($url);
    }

    protected function _internalGetInstanceIdByUrl($url) {
        return $url['pass'][0];
    }

}
