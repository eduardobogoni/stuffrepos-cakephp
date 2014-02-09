<?php

App::uses('AppHelper', 'View/Helper');

class FieldSetLayoutHelper extends AppHelper {

    public $helpers = array(
        'Html',
    );

    /**
     * HTML do fieldset.
     * @param array $lines
     * @param array $options
     * @return string
     */
    public function fieldSet($lines, $options = array()) {
        return $this->Html->tag(
                        'table',
                        $this->_rows(
                                $lines
                                , $this->Html->addClass($options, __CLASS__)
                        ), $options
        );
    }

    /**
     * HTML das linhas.
     * @param array $lines
     * @param array $options
     * @return string
     */
    private function _rows($lines, $options) {
        $b = '';
        $lineIndex = 0;
        $lineCount = count($lines);
        $maxColumnCount = $this->_maxColumnCount($lines);
        foreach ($lines as $line) {
            $b .= $this->_row($line,
                    array_merge(compact(
                                    'lineIndex', 'lineCount', 'maxColumnCount'
                            ), $options));
            $lineIndex++;
        }
        return $b;
    }

    /**
     * HTML da linha.
     * @param array $line
     * @param array $options
     * @return string
     */
    private function _row($line, $options) {
        $b = '';
        $columnIndex = 0;
        $columnCount = count($line);
        $columnExpandIndex = $this->_columnExpandIndex($line);
        foreach ($line as $label => $content) {
            $b .= $this->_column($label, $content,
                    array_merge(compact(
                                    'columnIndex', 'columnCount',
                                    'columnExpandIndex'
                            ), $options));
        }
        return $this->Html->tag('tr', $b);
    }

    /**
     * HTML da coluna (Label e valor)
     * @param string $label
     * @param string $content
     * @param array $options
     * @return string
     */
    private function _column($label, $content, $options) {
        $b = $this->Html->tag('th', $label);
        $b .= $this->Html->tag('td', $content,
                array(
            'colspan' => $this->_fieldColumnSpan($options)
        ));
        return $b;
    }

    /**
     * Atributo "colspan" da célula de valor de campo.
     * @param array $options
     * @return int
     */
    private function _fieldColumnSpan($options) {
        if ($options['columnIndex'] == $options['columnExpandIndex']) {
            return ($options['maxColumnCount'] * 2) -
                    ($options['columnCount'] - 1) * 2 -
                    1;
        } else {
            return 1;
        }
    }

    /**
     * Maior número de colunas entra as linhas.
     * @param array $lines
     * @return int
     */
    private function _maxColumnCount($lines) {
        return max(array_map('count', $lines));
    }

    /**
     * Índice da coluna da linha que se expandirá pelo
     * espaço restante
     * @param array $line
     */
    private function _columnExpandIndex($line) {
        foreach ($line as $index => $column) {
            if (!empty($column['expand']) && $column['expand']) {
                return $index;
            }
        }
        return 0;
    }

}
