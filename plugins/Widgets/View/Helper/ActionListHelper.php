<?php

App::uses('ArrayUtil', 'Base.Lib');
App::uses('AppHelper', 'View/Helper');

/**
 * Cria listas de links.
 */
class ActionListHelper extends AppHelper {

    const LAYOUT_LIST = 'line';
    const LAYOUT_TABLE = 'table';

    public $debug = false;
    public $helpers = array(
        'Html',
        'AccessControl.AccessControl',
        'Base.CakeLayers',
    );

    /**
     * @var array
     */
    public $settings = array(
        'beforeAll' => '',
        'afterAll' => '',
        'beforeEach' => '',
        'afterEach' => '',
        'tableLayoutBeforeAll' => '',
        'tableLayoutAfterAll' => '',
        'tableLayoutBeforeEach' => '',
        'tableLayoutAfterEach' => '',
        'id' => null,
        'controller' => null,
        'skipNoRequiredIdActions' => false,
        'shortFormat' => false,
        'skipActions' => array(),
        'layout' => self::LAYOUT_LIST
    );

    /**
     * Cria lista de links.
     * @param array $actions
     * @return string
     * @throws Exception
     */
    public function actionList($actions) {
        switch ($this->settings['layout']) {
            case self::LAYOUT_LIST:
                return $this->_outputActionsLine($this->_parseActions($actions));

            case self::LAYOUT_TABLE:
                return $this->_outputActionsTable($this->_parseActions($actions));

            default:
                throw new Exception("Layout not mapped: \"{$this->settings['layout']}\".");
        }
    }

    private function _parseActions($actions) {
        return array_map(function($value) {
            return array_merge(
                    array('post' => false, 'caption' => '?', 'linkOptions' => array())
                    , $value
            );
        }, $actions);
    }

    private function _outputActionsLine($actions) {
        $buffer = $this->settings['beforeAll'];
        foreach ($actions as $action) {
            $buffer .= $this->settings['beforeEach'] . $this->_buildActionLink($action) . $this->settings['afterEach'];
        }
        $buffer .= $this->settings['afterAll'];
        return $buffer;
    }

    private function _outputActionsTable($actions) {
        if (count($actions) >= 4) {
            $rows = floor(count($actions) / sqrt(count($actions)));
        } else {
            $rows = 1;
        }

        $columns = ceil(count($actions) / $rows);
        $cellWidth = ($columns == 0 ? '100%' : floor(100 / $columns) . '%');

        $b = $this->settings['tableLayoutBeforeAll'];
        $b .= '<table class="actionListHelperTableLayout">';
        $cell = 0;

        for ($row = 0; $row < $rows; $row++) {
            $b .= '<tr>';
            for ($column = 0; $column < $columns; $column++) {
                $index = $row * $columns + $column;
                $b .= "<td style='width: $cellWidth'>";
                if (!empty($actions[$index])) {
                    $b .= $this->settings['tableLayoutBeforeEach'];
                    $b .= $this->_buildActionLink($actions[$index]);
                    $b .= $this->settings['tableLayoutAfterEach'];
                } else {
                    $b .= '&nbsp;';
                }

                $b .= '</td>';
            }
            $b .= '</tr>';
        }

        $b .= '</table>';
        $b .= $this->settings['tableLayoutAfterAll'];
        return $b;
    }

    private function _buildActionLink($action) {
        $linkOptions = empty($action['linkOptions']) ? array() : $action['linkOptions'];
        $linkOptions['method'] = $action['post'] ? 'post' : 'get';
        $question = isset($action['question']) ? __d('widgets',$action['question']) : false;
        return $this->AccessControl->link(
                        $action['caption']
                        , $action['url']
                        , $linkOptions
                        , $question
        );
    }

}
