<?php

class CssShell extends Shell {

    /**
     * get the option parser.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        return $parser;
    }

    public function main() {
        $cssFile = $this->args[0];
        $css = file_get_contents($cssFile);
        $colors = $this->_getColorsCodes($css);
        $vars = $this->_variables($colors);

        file_put_contents('out.css', $this->_buildCss($css, $colors, $vars));
        file_put_contents('variables.ini', $this->_buildVariables($vars));
    }

    private function _variables($colors) {
        $index = 0;
        $vars = array();
        foreach (array_unique($colors) as $color) {
            $vars['color' . ($index + 1)] = $color;
            $index++;
        }
        return $vars;
    }

    private function _buildVariables($vars) {
        $b = '';
        foreach ($vars as $name => $value) {
            $b .= "$name=#$value\n";
        }
        return $b;
    }

    private function _buildCss($css, $colors, $vars) {
        foreach ($colors as $orig => $canonized) {
            $var = array_search($canonized, $vars);
            $css = preg_replace('/#' . $orig . '(?![0-9a-fA-F])/', '$' . $var, $css);
        }
        return $css;
    }

    private function _getColorsCodes($css) {
        if (preg_match_all('/#([0-9a-fA-F]{3,6})(?![0-9a-fA-F])/', $css, $colors)) {
            $uniq = array();
            foreach ($colors[1] as $c) {
                $uniq[$c] = $this->_canonicalColor($c);
            }
            return $uniq;
        } else {
            return array();
        }
    }

    private function _canonicalColor($color) {
        if (strlen($color) == 3) {
            $six = '';
            for ($k = 0; $k < strlen($color); ++$k) {
                $six .=$color[$k] . $color[$k];
            }
        } else {
            $six = $color;
        }

        return strtolower($six);
    }

}