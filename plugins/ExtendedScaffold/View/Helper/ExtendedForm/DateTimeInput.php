<?php

class DateTimeInput {

    public static function dateTime(ExtendedFormHelper $helper, $fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $attributes = array()) {
        $id = $helper->createNewDomId();
        $hiddenInput = self::_hiddenInput($helper, $fieldName, $selected);
        $visibleInput = self::_visibleInput($helper, $fieldName, $attributes);
        $initData = json_encode(array(
            '_patterns' => self::_getPatterns(self::_type($dateFormat, $timeFormat))
        ));
        return <<<EOT
<span id="$id" initCallback="ExtendedFormHelper.DateTimeInput.initInput" initData='$initData' >
    $hiddenInput
    $visibleInput
</span>
EOT;
    }
    
    private static function _type($dateFormat, $timeFormat) {
        if ($dateFormat && $timeFormat) {
            return 'datetime';
        } else if ($timeFormat) {
            return 'time';
        } else {
            return 'date';
        }
    }

    private function _hiddenInput(ExtendedFormHelper $helper, $fieldName, $selected = null) {
        $hiddenAttributes = array('subId' => 'hiddenInput',);
        if (isset($selected['value'])) {
            $hiddenAttributes['value'] = $selected['value'];
        }
        return $helper->hidden($fieldName, $hiddenAttributes);
    }

    private function _visibleInput(ExtendedFormHelper $helper, $fieldName, $attributes = array()) {
        $visibleInputName = $fieldName . '_masked';
        $helper->setEntity($visibleInputName);
        $visibleId = $helper->createNewDomId();
        return $helper->nonExtendedText(
                $visibleInputName, array_merge(
                        $attributes, array(
                                'id' => $visibleId,
                                'subId' => 'visibleInput',
                        )
                )
        );
    }

    private static function _getPatterns($fieldType) {
        switch ($fieldType) {
            case 'date':
                return array(
                    'mask' => 'd/m/y',
                    'emptyMask' => '__/__/____',
                    'serverFormat' => 'YYYY-MM-DD',
                    'guiFormat' => 'DD/MM/YYYY',
                );

            case 'time':
                return array(
                    'mask' => '99:99',
                    'emptyMask' => '__:__',
                    'serverFormat' => 'HH:mm',
                    'guiFormat' => 'HH:mm',
                );
                break;

            case 'datetime':
                return array(
                    'mask' => 'd/m/y 99:99',
                    'emptyMask' => '__/__/____ __:__',
                    'serverFormat' => 'YYYY-MM-DD HH:mm',
                    'guiFormat' => 'DD/MM/YYYY HH:mm',
                );
                break;

            default:
                throw new Exception("Field type unknown: \"$fieldType\"");
        }
    }

}
