<?php

App::uses('ArrayUtil', 'Base.Lib');
App::uses('AppHelper', 'View/Helper');

/**
 * Builds calendars.
 */
class CalendarHelper extends AppHelper {

    private $defaultOptions = array(
        'class' => 'CalendarHelper',
    );

    /**
     * 
     * @param array $entries array( DATE => HTML )
     * @param array $options
     * @return string
     */
    public function calendar($entries, $options = array()) {
        $options += $this->defaultOptions;
        $b = "<table class='{$options['class']}' >";
        $b .= "<thead>\n<tr>";
        foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $weekDay) {
            $b .= "<th>$weekDay</th>";
        }
        $b .= "</tr></thead>";
        $b .= "<tbody>\n<tr>\n";
        $date = $this->__firstDate($entries);
        $dayInterval = new DateInterval('P1D');
        $lastDate = $this->__lastDate($entries);
        $first = true;
        while ($date->getTimestamp() <= $lastDate->getTimestamp()) {
            if ($date->format('w') == 0 && !$first) {
                $b .= '</tr><tr>';
            }
            $first = false;
            $entry = array_key_exists($date->format('Y-m-d'), $entries) ?
                    $entries[$date->format('Y-m-d')] :
                    '&nbsp;';
            $b .= "<td><span class='entryDate'>{$date->format('d/m/y')}</span><span class='entryContent'>$entry</span></td>";
            $date->add($dayInterval);
        }
        $b .= "</tr>\n</tbody>\n";
        $b .= '</table>';
        return $b;
    }

    /**
     * 
     * @param type $entries
     * @return DateTime
     */
    private function __firstDate($entries) {
        $min = null;
        foreach (array_keys($entries) as $date) {
            if ($min === null || $date < $min) {
                $min = $date;
            }
        }
        $date = new DateTime($min);
        $date->sub(new DateInterval('P' . ($date->format('w')) . 'D'));
        return $date;
    }

    /**
     * 
     * @param array $entries
     * @return \DateTime
     */
    private function __lastDate($entries) {
        $max = null;
        foreach (array_keys($entries) as $date) {
            if ($max === null || $date > $max) {
                $max = $date;
            }
        }
        $date = new DateTime($max);
        $date->add(new DateInterval('P' . (6 - $date->format('w')) . 'D'));
        return $date;
    }

}
