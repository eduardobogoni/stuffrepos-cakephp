<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');

class ExtendedFieldsAccessControl {

    public static function sessionUserHasFieldAccess($extendedFieldsParserField) {
        return self::sessionUserHasFieldSetAccess($extendedFieldsParserField['options']);
    }

    public static function sessionUserHasFieldSetAccess($extendedFieldsParserFieldSet) {
        if (!empty($extendedFieldsParserFieldSet['accessObject'])) {
            if (empty($extendedFieldsParserFieldSet['accessObjectType'])) {
                return AccessControlComponent::sessionUserHasAccess($extendedFieldsParserFieldSet['accessObject']);
            } else {
                return AccessControlComponent::sessionUserHasAccess($extendedFieldsParserFieldSet['accessObject'], $extendedFieldsParserFieldSet['accessObjectType']);
            }
        } else {
            return true;
        }
    }

}
