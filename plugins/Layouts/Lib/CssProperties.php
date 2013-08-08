<?php

class CssProperties {

    /**
     *
     * @var array
     */
    private $properties;

    /**
     * 
     * @param array $properties
     */
    public function __construct($properties) {
        $this->properties = $properties;
    }

    /**
     * 
     * @param mixed $selectors String or array
     * @return string
     */
    public function build($selectors, $pseudoClass = false) {
        $b = $this->_selectors($selectors, $pseudoClass) . " {\n";
        foreach ($this->properties as $name => $value) {
            $b .= $this->_buildProperty($name, $value);
        }
        $b .= "}\n";

        return $b;
    }

    private function _selectors($selectors, $pseudoClass) {
        if (!is_array($selectors)) {
            $selectors = explode(',', $selectors);
        }

        $b = array();
        foreach ($selectors as $selector) {
            $s = trim($selector);
            if ($pseudoClass) {
                $s .= ':' . $pseudoClass;
            }
            $b[] = $s;
        }
        return implode(', ', $b);
    }

    /**
     * 
     * @param string $name
     * @param mixed $value String or array.
     * @return string
     */
    private function _buildProperty($name, $value) {
        $value = ArrayUtil::arraylize($value);

        $b = '';
        foreach ($value as $v) {
            $b .= "\t$name: $v;\n";
        }
        return $b;
    }

}