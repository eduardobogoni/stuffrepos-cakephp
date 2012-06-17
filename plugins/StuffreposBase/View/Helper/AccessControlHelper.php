<?php

App::import('Model', 'Usuario');

class AccessControlHelper extends Helper {

    public $helpers = array(
        'Html',
        'Form',
    );
    
    public function hasAccess($url) {        
        $AccessControlComponent = ClassRegistry::getObject('AccessControlComponent');        
        if (empty($AccessControlComponent)) {
            throw new Exception("Objeto 'AccessControlComponent' não foi encontrado em ClassRegistry (Foi adicionado como componente no controller?).");
        }
        return $AccessControlComponent->hasAccess($url);
    }

    public function output($url, $contentIfTrue, $contentIfFalse = '', $return = true) {
        $out = $this->hasAccess($url) ? $contentIfTrue : $contentIfFalse;
        return $this->Html->output($out, $return);
    }

    public function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true, $showTextIfAccessDenied = false) {
        return $this->output(
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
        return $this->output(
                        $url, $this->Html->image($image, array("alt" => $title, "title" => $title, 'url' => $url, "onclick" => (!empty($confirmationMessage) ? "return confirm('$confirmationMessage')" : "")))
        );
    }

}

?>
