<?php

App::uses('AppHelper', 'View/Helper');

class MenuHelper extends AppHelper {

    public $helpers = array(
        'AccessControl.AccessControl',
        'ExtendedScaffold.ScaffoldUtil',
    );

    public function __construct(\View $View, $settings = array()) {
        parent::__construct($View, $settings + array(
            'defaultCss' => true,
        ));

        if ($this->settings['defaultCss']) {
            $this->ScaffoldUtil->addCssLink('Widgets.MenuHelper.css');
        }
    }

    public function dropdown($entries, $mainEntriesEqualsWidth = false) {
        return $mainEntriesEqualsWidth ?
            $this->_dropDownMainEntriesEqualsWidth($entries) :
            $this->_dropDownCommon($entries);
    }

    private function _dropDownCommon($entries) {
        $buffer = "<ul class='MenuHelper'>";

        foreach ($this->_userMenu($entries) as $name => $entry) {
            if (is_array($entry)) {
                $buffer .= $this->_submenu($name, $entry, null);
            } else {
                $buffer .= $this->_link($name, $entry, null);
            }
        }

        $buffer .= "</ul>";
        return $buffer;
    }

    private function _dropDownMainEntriesEqualsWidth($entries) {
        $buffer = "<ul class='MenuHelper'>";
        $totalWidth = 100;
        $count = count($entries);

        foreach ($this->_userMenu($entries) as $name => $entry) {
            $width = floor($totalWidth / $count) - 1;
            if (is_array($entry)) {
                $buffer .= $this->_submenu($name, $entry, $width);
            } else {
                $buffer .= $this->_link($name, $entry, $width);
            }
        }

        $buffer .= "</ul>";
        return $buffer;
    }

    private function _listItem($content, $width) {
        $b = '<li';
        if ($width) {
            $b .= " style='width: $width%'";
        }
        $b .= '>' . $content . '</li>';
        return $b;
    }

    private function _link($label, $uri, $width = null) {
        return $this->_listItem($this->AccessControl->link($label, $uri), $width);
    }

    private function _submenu($label, $entries, $width = null) {
        $buffer = "<a href='#'>$label &darr;</a>";
        $buffer .= "<ul class='sub_menu'>";
        foreach ($entries as $name => $entry) {
            if (is_array($entry)) {
                $buffer .= $this->_submenu($name, $entry);
            } else {
                $buffer .= $this->_link($name, $entry);
            }
        }
        $buffer .= "</ul>";

        return $this->_listItem($buffer, $width);
    }

    private function _userMenu($entries) {
        $userEntries = array();
        foreach ($entries as $name => $value) {
            if (is_array($value)) {
                $subUserEntries = $this->_userMenu($value);
                if (!empty($subUserEntries)) {
                    $userEntries[$name] = $subUserEntries;
                }
            } else {
                if ($this->AccessControl->hasAccessByUrl($value)) {
                    $userEntries[$name] = $value;
                }
            }
        }

        return $userEntries;
    }

}

?>