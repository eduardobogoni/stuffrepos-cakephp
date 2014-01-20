<?php

App::uses('ModelBehavior', 'Model');

class TimeZoneBehavior extends ModelBehavior {

    const UTC_TIMEZONE = 'GMT';

    /**
     *
     * @var array
     */
    private $config;

    public function setup(\Model $model, $config = array()) {
        parent::setup($model, $config);
        $this->config[$model->name] = $config;
        foreach ($this->config[$model->name] as $field) {
            $model->validate[$this->_timeZoneField($field)]['timezoneFormat'] = array(
                'rule' => 'isValidTimeZone',
                'message' => __('It is not a valid Timezone format'),
                'allowEmpty' => true,
                'required' => false,
            );
        }
    }

    public function beforeSave(\Model $model, $options = array()) {
        if (parent::beforeSave($model, $options) === false) {
            return false;
        }
        $this->_toDatabase($model, $model->data);
        return true;
    }

    public function afterSave(\Model $model, $created, $options = array()) {
        if (parent::afterSave($model, $created, $options) === False) {
            return false;
        }
        $this->_fromDatabase($model, $model->data);
        return true;
    }

    public function afterFind(\Model $model, $results, $primary = false) {
        if (is_array($results)) {
            foreach (array_keys($results) as $k) {
                $this->_fromDatabase($model, $results[$k]);
            }
        }
        return $results;
    }

    public function isValidTimeZone(\Model $model, $check) {
        foreach ($check as $value) {
            if (!in_array($value, timezone_identifiers_list())) {
                return false;
            }
        }
        return true;
    }

    /**
     * 
     * @param Model $model
     * @param array $row
     * @throws Exception
     */
    private function _toDatabase(\Model $model, &$row) {
        foreach ($this->config[$model->name] as $field) {
            $this->_assertTimeZoneField($model, $row, $field);
            $this->_convertDateFieldToUtc($model, $row, $field);
        }
        if (empty($row[$model->alias][$this->_timeZoneField($field)])) {
            throw new Exception("{$this->_timeZoneField($field)} field is empty");
        }
    }

    /**
     * 
     * @param Model $model
     * @param array $row
     */
    private function _fromDatabase(\Model $model, &$row) {
        foreach ($this->config[$model->name] as $field) {
            if (!empty($row[$model->alias][$field])) {
                $row[$model->alias][$field] = $this->_fromUtc(
                        $row[$model->alias][$field]
                        , $row[$model->alias][$this->_timeZoneField($field)]
                );
            }
        }
    }

    private function _assertTimeZoneField(\Model $model, &$row, $field) {
        if (empty($row[$model->alias][$this->_timeZoneField($field)])) {
            $row[$model->alias][$this->_timeZoneField($field)] = date_default_timezone_get();
        }
    }

    private function _convertDateFieldToUtc(\Model $model, &$row, $field) {
        if (!empty($row[$model->alias][$field])) {
            $row[$model->alias][$field] = $this->_toUtc(
                    $row[$model->alias][$field]
                    , $row[$model->alias][$this->_timeZoneField($field)]
            );
        }
    }

    /**
     * 
     * @param string $date
     * @param string $timeZone
     * @return string
     */
    private function _toUtc($date, $timeZone) {
        return $this->_convertDateToZone(
                        $date
                        , $timeZone
                        , self::UTC_TIMEZONE);
    }

    /**
     * 
     * @param string $date
     * @param string $timeZone
     * @return string
     */
    private function _fromUtc($date, $timeZone) {
        return $this->_convertDateToZone(
                        $date
                        , self::UTC_TIMEZONE
                        , $timeZone);
    }

    /**
     * 
     * @param string $date
     * @param string $from
     * @param string $to
     * @return string
     */
    private function _convertDateToZone($date, $from, $to) {
        $date = new DateTime($date, new DateTimeZone($from));
        $date->setTimezone(new DateTimeZone($to));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 
     * @param string $field
     * @return string
     */
    private function _timeZoneField($field) {
        return $field . '_timezone';
    }

}
