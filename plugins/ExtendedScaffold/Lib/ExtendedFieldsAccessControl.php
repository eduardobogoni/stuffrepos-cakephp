<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::uses('ExtendedFieldSet', 'ExtendedScaffold.Lib');

class ExtendedFieldsAccessControl {

    public static function sessionUserHasFieldAccess(\ExtendedField $field) {
        return self::_sessionUserHasAccess(
                        $field->getAccessObject()
                        , $field->getAccessObjectType()
        );
    }

    public static function sessionUserHasFieldSetAccess(\ExtendedFieldSet $fieldSet) {
        return self::_sessionUserHasAccess(
                        $fieldSet->getAccessObject()
                        , $fieldSet->getAccessObjectType()
        );
    }

    private static function _sessionUserHasAccess($accessObject, $accessObjectType) {
        if ($accessObject) {
            return $accessObjectType ?
                    AccessControlComponent::sessionUserHasAccess(
                            $accessObject
                            , $accessObjectType
                    ) :
                    AccessControlComponent::sessionUserHasAccess(
                            $accessObject
            );
        } else {
            return true;
        }
    }

    public static function parseFieldsets($fieldsData, $defaultModel = null) {
        $fieldSets = ExtendedFieldsParser::parseFieldsets($fieldsData, $defaultModel);
        $ret = array();
        foreach ($fieldSets as $fieldSet) {
            if ($acFieldSet = self::_buildFieldSet($fieldSet)) {
                $ret[] = $fieldSet;
            }
        }
        return $ret;
    }

    private static function _buildFieldSet(\ExtendedFieldSet $fieldSet) {
        if (!self::sessionUserHasFieldSetAccess($fieldSet)) {
            return null;
        }
        $lines = array();
        foreach ($fieldSet->getLines() as $line) {
            $acLine = self::_buildLine($line);
            if (!empty($acLine)) {
                $lines[] = $acLine;
            }
        }
        return empty($lines) ?
                false :
                new ExtendedFieldSet($lines, $fieldSet->getOptions());
    }

    private static function _buildLine(\ExtendedFieldsLine $line) {
        $acFields = array();
        foreach ($line->getFields() as $field) {
            if (self::sessionUserHasFieldAccess($field)) {
                $acFields[] = $field;
            }
        }
        return $acFields;
    }

}
