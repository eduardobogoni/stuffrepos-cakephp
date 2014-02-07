<?php

App::uses('CssProperties', 'Layouts.Lib');
App::uses('ArrayUtil', 'Base.Lib');

class CssBox {

    private $borderRadius = '3px';
    private $borderWidth = '1px';
    private $borderStyle = 'solid';
    private $borderColor = '#7d99ca';
    private $backgroundColorStart = '#d3d3d3';
    private $backgroundColorEnd = '#707070';
    private $padding = '10px';
    private $autoBuildHover = false;

    public function __construct($options) {
        foreach ($options as $name => $value) {
            if (isset($this->{$name})) {
                $this->{$name} = $value;
            }
        }
    }

    public function build($selectors) {
        $b = $this->_defaultProperties()->build($selectors);
        if ($this->autoBuildHover) {
            $b .= $this->_hoverProperties()->build($selectors, 'hover');
        }
        return $b;
    }

    /**
     * 
     * @param boolean $invertBackgroundColorStartEnd
     * @return CssProperties
     */
    private function _defaultProperties() {
        $props = new CssProperties(array(
            '-webkit-border-radius' => $this->borderRadius,
            '-moz-border-radius' => $this->borderRadius,
            'border-radius' => $this->borderRadius,
            'border-width' => $this->borderWidth,
            'border-style' => $this->borderStyle,
            'border-color' => $this->borderColor,            
            'padding' => $this->padding,
            'text-decoration' => 'none',
            'display' => 'inline-block',            
            'filter' => "progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr={$this->backgroundColorStart}, endColorstr={$this->backgroundColorEnd})",
        ));
        return $props->add($this->_gradientProperties(false));
    }
    
    /**
     * @return CssProperties
     */
    private function _hoverProperties() {
        return $this->_gradientProperties(true);
    }
    
    /**
     * @return CssProperties
     */
    private function _gradientProperties($invertBackgroundColorStartEnd) {
        if ($invertBackgroundColorStartEnd) {
            $backgroundColorEnd = $this->backgroundColorStart;
            $backgroundColorStart = $this->backgroundColorEnd;
        } else {
            $backgroundColorStart = $this->backgroundColorStart;
            $backgroundColorEnd = $this->backgroundColorEnd;
        }

        return new CssProperties(array(
            'background-color' => $backgroundColorStart,
            'background-image' => array(
                "-webkit-gradient(linear, left top, left bottom, from({$backgroundColorStart}), to({$backgroundColorEnd}))",
                "-webkit-linear-gradient(top, {$backgroundColorStart}, {$backgroundColorEnd})",
                "-moz-linear-gradient(top, {$backgroundColorStart}, {$backgroundColorEnd})",
                "-ms-linear-gradient(top, {$backgroundColorStart}, {$backgroundColorEnd})",
                "-o-linear-gradient(top, {$backgroundColorStart}, {$backgroundColorEnd})",
                "linear-gradient(to bottom, {$backgroundColorStart}, {$backgroundColorEnd})",
            ),
            'filter' => "progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr={$this->backgroundColorStart}, endColorstr={$this->backgroundColorEnd})",
        ));
    }

}
