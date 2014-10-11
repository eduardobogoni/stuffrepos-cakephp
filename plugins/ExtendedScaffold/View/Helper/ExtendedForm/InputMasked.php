<?php

class InputMasked {

    public static function maskedText(ExtendedFormHelper $helper, $fieldName, $options) {
        $id = $helper->createNewDomId();
        $hiddenInput = self::__hiddenInput($helper, $fieldName);
        $visibleInput = self::__visibleInput($helper, $fieldName, $options);
        $initData = json_encode(array(
            'mask' => $options['mask'],
        ));
        return <<<EOT
<span id="$id" initCallback="ExtendedFormHelper.InputMasked.initInput" initData='$initData' >
    $hiddenInput
    $visibleInput
</span>
EOT;
    }

    private static function __hiddenInput($helper, $fieldName) {
        return $helper->hidden($fieldName, array('subId' => 'hiddenInput'));
    }

    private static function __visibleInput($helper, $fieldName, $attributes) {
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

}
