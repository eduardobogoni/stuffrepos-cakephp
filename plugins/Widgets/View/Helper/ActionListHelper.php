<?php

App::uses('ArrayUtil', 'Base.Lib');
App::uses('AppHelper', 'View/Helper');

/**
 * Cria listas de links.
 * 
 * Action:
 * - post
 * - caption
 * - linkOptions
 * - url
 */
class ActionListHelper extends AppHelper {

    const LAYOUT_LIST = 'list';
    const LAYOUT_TABLE = 'table';

    public $helpers = array(
        'AccessControl.AccessControl',
    );

    /**
     * @var array
     */
    public $settings = array(
        'listLayoutBeforeAll' => '',
        'listLayoutAfterAll' => '',
        'listLayoutBeforeEach' => '',
        'listLayoutAfterEach' => '',
        'tableLayoutBeforeAll' => '',
        'tableLayoutAfterAll' => '',
        'tableLayoutBeforeEach' => '',
        'tableLayoutAfterEach' => '',
        'layout' => self::LAYOUT_LIST
    );

    /**
     * Cria lista de links.
     * @param array $actions
     * @return string
     * @throws Exception
     */
    public function actionList($actions, $options = array()) {
        $options = array_merge($this->settings, $options);
        switch ($options['layout']) {
            case self::LAYOUT_LIST:
                return $this->_outputActionsList($this->_parseActions($actions), $options);

            case self::LAYOUT_TABLE:
                return $this->_outputActionsTable($this->_parseActions($actions), $options);

            default:
                throw new Exception("Layout not mapped: \"{$options['layout']}\".");
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

    /**
     * 
     * @param array $actions
     * @param array $options
     * @return string
     */
    private function _outputActionsList($actions, $options) {
        $buffer = $options['listLayoutBeforeAll'];
        foreach ($actions as $action) {
            $buffer .= $options['listLayoutBeforeEach'] . $this->_buildActionLink($action) . $options['listLayoutAfterEach'];
        }
        $buffer .= $options['listLayoutAfterAll'];
        return $buffer;
    }

    /**
     * 
     * @param array $actions
     * @return string
     */
    private function _outputActionsTable($actions, $options) {
        if (count($actions) >= 4) {
            $rows = floor(count($actions) / sqrt(count($actions)));
        } else {
            $rows = 1;
        }

        $columns = ceil(count($actions) / $rows);
        $cellWidth = ($columns == 0 ? '100%' : floor(100 / $columns) . '%');

        $b = $options['tableLayoutBeforeAll'];
        $b .= '<table class="actionListHelperTableLayout">';

        for ($row = 0; $row < $rows; $row++) {
            $b .= '<tr>';
            for ($column = 0; $column < $columns; $column++) {
                $index = $row * $columns + $column;
                $b .= "<td style='width: $cellWidth'>";
                if (!empty($actions[$index])) {
                    $b .= $options['tableLayoutBeforeEach'];
                    $b .= $this->_buildActionLink($actions[$index]);
                    $b .= $options['tableLayoutAfterEach'];
                } else {
                    $b .= '&nbsp;';
                }

                $b .= '</td>';
            }
            $b .= '</tr>';
        }

        $b .= '</table>';
        $b .= $options['tableLayoutAfterAll'];
        return $b;
    }

    /**
     * 
     * @param array $action
     * @return string
     */
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
