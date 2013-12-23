<?php

App::uses('ViewUtilHelper', 'ExtendedScaffold.View/Helper');

class ViewUtilExtendedFieldset {

    private $legend;
    private $lines = array();
    private $scaffoldVars;
    private $parent;

    public function __construct(ViewUtilHelper $parent, $data, &$scaffoldVars) {
        $this->parent = $parent;
        $this->legend = empty($data['legend']) ? null : $data['legend'];
        foreach ($data['lines'] as $lineData) {
            $this->lines[] = new ExtendedLine($this, $lineData);
        }
        $this->scaffoldVars = &$scaffoldVars;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getInstance() {
        return $this->scaffoldVars['instance'];
    }

    public function getAssociations() {
        return $this->scaffoldVars['associations'];
    }

    public function getModelClass() {
        return $this->scaffoldVars['modelClass'];
    }

    public function getLinesFieldCountLcd() {
        $previous = 1;
        foreach ($this->lines as $line) {
            $lcd = Basics::lcd($previous, $line->getFieldCount());
        }
        return $lcd;
    }

    public function output() {
        $b = '';
        if (!empty($this->legend)) {
            $b .= "<h3>{$this->legend}</h3>";
        }

        $b .= '<table class="viewFieldList">';
        $lineCount = 0;

        foreach ($this->lines as $line) {
            $b .= $line->output($lineCount++ % 2 == 0);
        }
        $b .= '</table>';

        return $b;
    }

    public function getFieldLabelWidth() {
        // Largura da tabela
        $tableWidth = 1;

        // Quantidade de campos da maior linha
        $maxLineFieldCount = $this->_getMaxLineFieldCount();

        // Largura de cada campo
        $fieldWidth = $tableWidth / $maxLineFieldCount;

        // Largura do label
        $labelWidth = $fieldWidth * ExtendedField::LABEL_WIDTH_PERCENT;

        // Formato para CSS
        $result = number_format($labelWidth * 100, 0) . '%';

        return $result;
    }

    public function getFieldInputWidth() {
        // Largura da tabela
        $tableWidth = 1;

        // Quantidade de campos da maior linha
        $maxLineFieldCount = $this->_getMaxLineFieldCount();

        // Largura de cada campo
        $fieldWidth = $tableWidth / $maxLineFieldCount;

        // Largura do label
        $inputWidth = $fieldWidth * (1 - ExtendedField::LABEL_WIDTH_PERCENT);

        // Formato para CSS
        $result = number_format($inputWidth * 100, 0) . '%';

        return $result;
    }

    private function _getMaxLineFieldCount() {
        $counts = array();
        foreach ($this->lines as $line) {
            $counts[] = $line->getFieldCount();
        }
        return max($counts);
    }

}
