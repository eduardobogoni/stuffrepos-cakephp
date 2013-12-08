<?php

class DateTimeInput {

    public static function dateTime(ExtendedFormHelper $helper, $fieldName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $attributes = array()) {
        $hiddenId = $helper->createNewDomId();
        $hiddenAttributes = array('id' => $hiddenId);
        if (isset($selected['value'])) {
            $hiddenAttributes['value'] = $selected['value'];
        }
        $hiddenInput = $helper->hidden($fieldName, $hiddenAttributes);
        $visibleInputName = $fieldName . '_masked';
        $helper->setEntity($visibleInputName);
        $visibleId = $helper->createNewDomId();
        $visibleInput = $helper->nonExtendedText(
                $visibleInputName, array_merge(
                        $attributes, array(
            'id' => $visibleId
                        )
                )
        );
        $fieldType = $helper->fieldDefinition($fieldName, 'type');
        $buffer = $hiddenInput . $visibleInput . self::_onReady(
                        $helper, $hiddenId, $visibleId, $fieldType);
        $helper->setEntity($fieldName);
        $helper->inputsOnSubmit->addInput($fieldType, $visibleId, $hiddenId);
        return $buffer;
    }

    public static function onSubmit($input) {
        $patterns = self::_getPatterns($input['type']);
        return <<<EOT
        {
            text = \$('#{$input['visibleInputId']}').val();
            switch(text) {
                case '{$patterns['emptyMask']}':
                case '':
                    date = '';
                    break;
                
                default:
                    date = moment(text, '{$patterns['guiFormat']}');
                    if (date.isValid()) {
                        date = date.format('{$patterns['serverFormat']}');
                    }
                    else {
                        date = 'invalidDate';
                    } 
            }
            \$('#{$input['hiddenInputId']}').val(date);
        }
EOT;
    }

    private static function _onReady(ExtendedFormHelper $helper, $hiddenId, $visibleId, $fieldType) {
        $patterns = self::_getPatterns($fieldType);
        return $helper->javascriptTag(
                        "\$(document).ready(function(){
   \$('#$visibleId').inputmask('{$patterns['mask']}');  //direct mask   
                if (\$('#$hiddenId').val()) {                
                    date = moment(\$('#$hiddenId').val(),'{$patterns['serverFormat']}');
                    if (date.isValid()) {
                        \$('#$visibleId').val(
                           date.format('{$patterns['guiFormat']}')
                        );
                    }
                
                }
});");
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
