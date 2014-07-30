<?php

App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::import('Lib', 'Base.ModelTraverser');
App::import('Lib', 'Base.Basics');
App::uses('ExtendedField', 'ExtendedScaffold.View/Helper/ViewUtil');
App::uses('ExtendedLine', 'ExtendedScaffold.View/Helper/ViewUtil');
App::uses('ViewUtilListFieldset', 'ExtendedScaffold.View/Helper/ViewUtil');

class ViewUtilHelper extends AppHelper {
    const VALUE_TYPE_UNKNOWN = 'unknown';
    const VALUE_TYPE_BOOLEAN = 'boolean';

    public $helpers = array(
        'Html',
        'AccessControl.AccessControl',
        'Base.CakeLayers',
        'ExtendedScaffold.FieldSetLayout',
        'ExtendedScaffold.Lists',
    );

    /**
     * @var int
     */
    private $viewFieldCount = 0;
    
    /**
     *
     * @var NumberFormatter
     */
    private $moneyFormatter;
    
    public function __construct(\View $View, $settings = array()) {
        parent::__construct($View, $settings);       
        $this->moneyFormatter = NumberFormatter::create('pt_BR', NumberFormatter::DECIMAL);
        $this->moneyFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $this->moneyFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    }

    public function date($value) {
        return $this->_isDate($value);
    }

    private function _isDate($value) {
        if (($timestamp = strtotime(strval($value))) && preg_match('/\d/', strval($value))) {
            $dateOnly = date('Y-m-d', $timestamp);
            $dateOnlyTimestamp = strtotime($dateOnly);
            if ($dateOnlyTimestamp == $timestamp) {
                return date('d/m/Y', $timestamp);
            } else {
                return date('d/m/Y G:i', $timestamp);
            }
        } else if (is_array($value) && isset($value['month']) && isset($value['day']) && isset($value['year'])) {
            $timestamp = mktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
            return date('d/m/Y', $timestamp);
        } else if (is_array($value) && isset($value['month']) && isset($value['year'])) {
            $timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
            return date('m/Y', $timestamp);
        } else {
            return false;
        }
    }

    private function _isDecimal($value) {
        return preg_match('/^\d+(\.\d+)?$/', strval($value));
    }

    public function yesNo($value) {        
        if ($value) {
            return __d('extended_scaffold','Yes', true);
        } else {
            return __d('extended_scaffold','No', true);
        }
    }
    
    public function money($value) {        
        if (!is_float($value)) {
            $value = floatval($value);
        }
        return $this->moneyFormatter->format($value);
    }

    public function decimal($value) {
        return str_replace('.', ',', strval($value));
    }

    public function autoFormat($value) {
        if ($this->_isDecimal($value)) {
            return $this->decimal($value);
        } else if (($formated = $this->_isDate($value)) !== false) {
            return $formated;
        } else {
            return $this->string($value);
        }
    }

    /**
     *
     * @param string $string
     * @param string $mask
     * @return string
     */
    public function stringMask($string, $mask) {
        $stringLength = strlen($string);
        $maskLength = strlen($mask);

        $s = 0;
        $m = 0;
        $b = '';

        while ($m < $maskLength) {
            if ($m < $maskLength) {
                if (in_array($mask[$m], array('a', '9', '*'))) {
                    if ($s < $stringLength) {
                        $b .= $string[$s];
                        $s++;
                    }
                    else {
                        $b .= '_';
                    }
                    
                }
                else {
                    $b .= $mask[$m];
                }
                $m++;
            }
        }

        return $b;
    }
    
    public function string($value) {
        return nl2br(strip_tags($value));
    }

}
