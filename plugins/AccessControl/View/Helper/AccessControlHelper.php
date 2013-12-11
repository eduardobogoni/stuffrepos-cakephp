<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('Helper', 'View');

class AccessControlHelper extends Helper {

    public $helpers = array(
        'Html',
        'Form',
    );

    public function __call($method, $params) {
        if (preg_match('/^hasAccessBy(.+)$/', $method, $matches)) {
            if (count($params) < 1) {
                trigger_error(__('Missing argument 1 for %1$s::%2$s', get_class($this), $method), E_USER_ERROR);
            }

            return AccessControlComponent::sessionUserHasAccess(
                    $params[0], Inflector::variable($matches[1])
            );
        }

        return parent::__call($method, $params);
    }

    public function restrictedOutput($url, $contentIfTrue, $contentIfFalse = '', $return = true) {
        $out = $this->hasAccessByUrl($url) ? $contentIfTrue : $contentIfFalse;
        return $this->Html->output($out, $return);
    }

    public function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true, $showTextIfAccessDenied = false) {
        return $this->restrictedOutput(
                    $url, 
                !empty($htmlAttributes['method']) && $htmlAttributes['method'] == 'post'
                ? $this->Form->postlink($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle)
                : $this->Html->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle), ($showTextIfAccessDenied ? $title : '')
        );
    }

    public function linkOrText($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true, $showTextIfAccessDenied = false) {
        return $this->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle, true);
    }

    public function image($title, $image, $url, $confirmationMessage = null) {
        return $this->restrictedOutput(
                        $url, $this->Html->image($image, array("alt" => $title, "title" => $title, 'url' => $url, "onclick" => (!empty($confirmationMessage) ? "return confirm('$confirmationMessage')" : "")))
        );
    }

}

?>
