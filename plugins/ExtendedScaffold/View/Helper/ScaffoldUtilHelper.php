<?php

App::uses('AppHelper', 'View/Helper');

class ScaffoldUtilHelper extends AppHelper {

    public $helpers = array('Javascript');
    private $javascriptLinks = array();
    
    public function javascriptLinks() {                        
        $b = '';        
        if (!empty($this->javascriptLinks)) {
            foreach($this->javascriptLinks as $file) {
                
                $b .= $this->Javascript->link($file);
            }
        }
        
        return $b;        
    }
    
    public function addJavascriptLink($file) {
        $this->javascriptLinks[] = $file;
    }

}

?>
