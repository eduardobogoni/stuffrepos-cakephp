<?php

class LayoutsHelper extends Helper {
    
    public $helpers = array(
        'Html',
    );
    
    public function css($path, $rel = null, $options = array()) {
        return $this->Html->css(Router::url(array(
            'plugin' => 'layouts',
            'controller' => 'css',
            'action' => 'process',
            0 => $path
        ), true), $rel, $options);
    }

    public function cssBox($selectors, $cssOptions = array(), $rel = null, $options = array()) {
        return $this->Html->css(Router::url(array(
            'plugin' => 'layouts',
            'controller' => 'css',
            'action' => 'css_box',
            0 => $selectors,
        ) + $cssOptions, true), $rel, $options);
    }
}

?>
