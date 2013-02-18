<?php

class HtmlGrid {

    private $cells = array();

    public function __construct($attributes = array()) {
        $this->attributes = $attributes;
    }

    /**
     * 
     * @param int $x
     * @param int $y
     * @return HtmlGrid_Cell
     */
    public function cell($x, $y) {
        if (empty($this->cells[$x][$y])) {
            $this->cells[$x][$y] = new HtmlGrid_Cell($x, $y);
        }

        return $this->cells[$x][$y];
    }

    public function hasCell($x, $y) {
        return !empty($this->cells[$x][$y]);
    }

    public function cells() {
        $array = array();
        foreach ($this->cells as $x => $ys) {
            foreach ($ys as $y => $cell) {
                $array[] = $cell;
            }
        }
        return $array;
    }

    public function attributes() {
        return $this->attributes;
    }

    public function out() {
        $tableOut = new _HtmlGrid_TableOut($this);
        return $tableOut->out();
    }

    public function outDebug() {
        $tableOut = new _HtmlGrid_TableOut($this);
        return $tableOut->outDebug();
    }

}

class HtmlGrid_Cell {

    private $content = '';
    private $width = 1;
    private $height = 1;
    private $left;
    private $top;
    private $attributes = array();

    public function __construct($left, $top) {
        $this->left = $left;
        $this->top = $top;
    }

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }

    public function getWidth() {
        return $this->width;
    }

    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getLeft() {
        return $this->left;
    }

    public function getTop() {
        return $this->top;
    }

    public function getRight() {
        return $this->left + $this->width - 1;
    }

    public function getBottom() {
        return $this->top + $this->height - 1;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function setAttribute($name, $value) {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function getAttribute($name) {
        return empty($this->attributes[$name]) ?
            null :
            $this->attributes[$name];
    }

    public function addClass($class) {
        if (empty($this->attributes['class']) || trim($this->attributes['class']) == '') {
            $this->attributes['class'] = $class;
        } else {
            $this->attributes['class'] .= ' ' . $class;
        }
        return $this;
    }

    public function hasClass($class) {
        if (empty($this->attributes['class'])) {
            return false;
        } else {
            return array_search($class, explode(' ', $this->attributes['class'])) !== false;
        }
    }

}

class _HtmlGrid_TableOut {

    /**
     *
     * @var HtmlGrid
     */
    private $grid;

    public function __construct(HtmlGrid $grid) {
        $this->grid = $grid;
    }

    public function out() {
        $b = '<table';
        foreach ($this->grid->attributes() as $key => $value) {
            $b .= " $key=\"$value\"";
        }
        $b .= '>';
        foreach ($this->_buildTableRows() as $row) {
            $b .= $this->_outTableRow($row);
        }
        $b .= "</table>\n";
        return $b;
    }

    public function outDebug() {
        $rowsCount = $this->_tableRowsCount();
        $columnsCount = $this->_tableColumnsCount();

        $tdStyle = 'style="border: thin solid black; font-size: smaller"';

        $b = '<table style="border: thin solid black; border-collapse: collapse">';
        $b .= '<tr>';
        $b .= "<th $tdStyle>Rows: $rowsCount/Col: $columnsCount</th>";
        for ($x = 0; $x < $columnsCount; $x++) {
            $b .= "<th $tdStyle>";
            $b .= $x;
            $b .= '</th>';
        }
        $b .= "</tr>\n";
        for ($y = 0; $y < $rowsCount; $y++) {
            $b .= '<tr>';
            $b .= "<th $tdStyle>";
            $b .= $y;
            $b .= '</th>';
            for ($x = 0; $x < $columnsCount; $x++) {
                $b .= "<td $tdStyle>";
                if ($this->grid->hasCell($x, $y)) {
                    $cell = $this->grid->cell($x, $y);
                    $b .= "<table>";
                    $b .= "<tr><th $tdStyle>Left:</th><td $tdStyle>{$cell->getLeft()}</td></tr>";
                    $b .= "<tr><th $tdStyle>Top:</th><td $tdStyle>{$cell->getTop()}</td></tr>";
                    $b .= "<tr><th $tdStyle>Class:</th><td $tdStyle>{$cell->getAttribute('class')}</td></tr>";
                    $b .= "</table>";
                } else {
                    $b .= '-';
                }
                $b .= '</td>';
            }
            $b .= "</tr>\n";
        }
        $b .= "</table>\n";

        return $b;
    }

    private function _outTableRow($row) {
        $b = '<tr>';
        foreach ($row as $cell) {
            $b .= $this->_outTableRowCell($cell);
        }
        $b .= "</tr>\n";
        return $b;
    }

    private function _outTableRowCell($cell) {
        $b = '<td';
        foreach ($cell['attributes'] as $key => $value) {
            $b .= " $key='$value'";
        }
        $b .= '>';
        $b .= $cell['content'];
        $b .= "</td>\n";

        return $b;
    }

    private function _buildTableRows() {
        $columnCount = $this->_tableColumnsCount();
        $rowCount = $this->_tableRowsCount();
        $map = $this->_buildTableCellsMap();

        $emptyCell = array(
            'attributes' => array(),
            'content' => ''
        );

        $rows = array();

        for ($y = 0; $y < $rowCount; $y++) {
            $row = array();
            for ($x = 0; $x < $columnCount; $x++) {
                if (empty($map[$x][$y])) {
                    $row[] = $emptyCell;
                } else if ($map[$x][$y] instanceof HtmlGrid_Cell) {
                    $attributes = array(
                        'rowSpan' => $map[$x][$y]->getHeight(),
                        'colSpan' => $map[$x][$y]->getWidth(),
                    );

                    foreach ($map[$x][$y]->getAttributes() as $name => $value) {
                        $attributes[$name] = $value;
                    }

                    $row[] = array(
                        'attributes' => $attributes,
                        'content' => $map[$x][$y]->getContent()
                    );
                }
            }

            $rows[] = $row;
        }

        //debug(compact('columnCount', 'rowCount', 'map', 'rows'));

        return $rows;
    }

    private function _buildTableCellsMap() {
        $map = array();
        foreach ($this->grid->cells() as $cell)
            for ($x = $cell->getLeft(); $x <= $cell->getRight(); $x++) {
                for ($y = $cell->getTop(); $y <= $cell->getBottom(); $y++) {
                    $map[$x][$y] = $x == $cell->getLeft() && $y == $cell->getTop() ?
                        $cell :
                        true;
                }
            }


        return $map;
    }

    private function _tableColumnsCount() {
        $max = -1;
        foreach ($this->grid->cells() as $cell) {
            if ($cell->getRight() > $max) {
                $max = $cell->getRight();
            }
        }
        return $max + 1;
    }

    private function _tableRowsCount() {
        $max = -1;
        foreach ($this->grid->cells() as $cell) {
            if ($cell->getBottom() > $max) {
                $max = $cell->getBottom();
            }
        }
        return $max + 1;
    }

}