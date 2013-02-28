<?php

App::uses('AppHelper', 'View/Helper');

class ScaffoldUtilHelper extends AppHelper {

    public $helpers = array('Html');
    private $javascriptLinks = array();
    private $cssLinks = array();

    public function links() {
        $b = '';

        foreach ($this->javascriptLinks as $file) {
            $b .= $this->Html->script($file);
        }
        
        foreach ($this->cssLinks as $file) {
            $b .= $this->Html->css($file);
        }

        return $b;
    }

    public function addJavascriptLink($file) {
        $this->javascriptLinks[] = $file;
    }

    public function addCssLink($file) {
        $this->cssLinks[] = $file;
    }

}

?>
