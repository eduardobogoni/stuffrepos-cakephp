<?php

App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('ExtendedFieldsParser', 'ExtendedScaffold.Lib');
App::uses('FieldSetDefinition', 'ExtendedScaffold.Lib');

class ExtendedFieldsAccessControl {

    public static function sessionUserHasFieldAccess(\FieldDefinition $field, $readOnly) {
        return self::_sessionUserHasAccessByOptions(
                        $field->getOptions()
                        , $readOnly
        );
    }

    public static function sessionUserHasFieldSetAccess(\FieldSetDefinition $fieldSet, $readOnly) {
        return self::_sessionUserHasAccessByOptions(
                        $fieldSet->getOptions()
                        , $readOnly
        );
    }

    private static function _sessionUserHasAccessByOptions($options, $readOnly) {
        $defaultAccess = self::_sessionUserAccess($options['accessObject'], $options['accessObjectType']);
        $readAccess = self::_sessionUserAccess($options['readAccessObject'], $options['accessObjectType']);
        $result = ($defaultAccess == 'allow') ||
                ($defaultAccess == false && $readAccess != 'deny') ||
                ($readOnly && $readAccess == 'allow');
        return $result;
    }

    private static function _sessionUserAccess($accessObject, $accessObjectType) {
        if ($accessObject) {
            $hasAccess = $accessObjectType ?
                    AccessControlComponent::sessionUserHasAccess(
                            $accessObject
                            , $accessObjectType
                    ) :
                    AccessControlComponent::sessionUserHasAccess(
                            $accessObject
            );
            return $hasAccess ? 'allow' : 'deny';
        } else {
            return false;
        }
    }

    public static function parseFieldsets($fieldsData, $readonly, $defaultModel = null) {
        $fieldSets = ExtendedFieldsParser::parseFieldsets($fieldsData, $defaultModel);
        $ret = array();
        foreach ($fieldSets as $fieldSet) {
            if ($acFieldSet = self::_buildFieldSet($fieldSet, $readonly)) {
                $ret[] = $acFieldSet;
            }
        }
        return $ret;
    }

    private static function _buildFieldSet(\FieldSetDefinition $fieldSet, $readonly) {
        if (!self::sessionUserHasFieldSetAccess($fieldSet, $readonly)) {
            return null;
        }
        $lines = array();
        foreach ($fieldSet->getLines() as $line) {
            if ($acLine = self::_buildLine($line, $readonly)) {
                $lines[] = $acLine;
            }
        }
        return empty($lines) ?
                false :
                new FieldSetDefinition($lines, $fieldSet->getOptions());
    }

    private static function _buildLine(\FieldRowDefinition $line, $readonly) {
        $acFields = array();
        foreach ($line->getFields() as $field) {
            if (self::sessionUserHasFieldAccess($field, $readonly)) {
                $acFields[] = $field;
            }
        }
        return empty($acFields) ?
                false :
                new FieldRowDefinition($acFields);
    }

}
