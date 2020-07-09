<?php

namespace InfyOm\Generator\Utils;

use InfyOm\Generator\Common\GeneratorField;

class HTMLFieldGenerator
{
    public static function generateHTML(GeneratorField $field, $templateType, $localized = false, $field_folder = 'fields')
    {
        $fieldTemplate = '';

        $localized = ($localized) ? '_locale' : '';
        switch ($field->htmlType) {
            case 'text':
            case 'textarea':
            case 'date':
            case 'file':
            case 'email':
            case 'password':
            case 'number':
                $fieldTemplate = get_template('scaffold.'.$field_folder.'.'.$field->htmlType.$localized, $templateType);
                break;
            case 'select':
            case 'enum':
            case 'selectize':
                $fieldTemplate = get_template('scaffold.'.$field_folder.'.select'.$localized, $templateType);
                if ($field->htmlType == 'selectize')
                    $fieldTemplate = get_template('scaffold.'.$field_folder.'.selectize'.$localized, $templateType);
                $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);
                if (count($radioLabels))
                    $replacement = GeneratorFieldsInputUtil::prepareKeyValueArrayStr($radioLabels);
                else
                    $replacement = 'null';

                $fieldTemplate = str_replace(
                    '$INPUT_ARR$',
                    $replacement,
                    $fieldTemplate
                );
                break;
            case 'checkbox':
                $fieldTemplate = get_template('scaffold.'.$field_folder.'.checkbox'.$localized, $templateType);
                if (count($field->htmlValues) > 0) {
                    $checkboxValue = $field->htmlValues[0];
                } else {
                    $checkboxValue = 1;
                }
                $fieldTemplate = str_replace('$CHECKBOX_VALUE$', $checkboxValue, $fieldTemplate);
                break;
            case 'radio':
                $fieldTemplate = get_template('scaffold.'.$field_folder.'.radio_group'.$localized, $templateType);
                $radioTemplate = get_template('scaffold.'.$field_folder.'.radio'.$localized, $templateType);

                $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                $radioButtons = [];
                foreach ($radioLabels as $label => $value) {
                    $radioButtonTemplate = str_replace('$LABEL$', $label, $radioTemplate);
                    $radioButtonTemplate = str_replace('$VALUE$', $value, $radioButtonTemplate);
                    $radioButtons[] = $radioButtonTemplate;
                }
                $fieldTemplate = str_replace('$RADIO_BUTTONS$', implode("\n", $radioButtons), $fieldTemplate);
                break;
            case 'toggle-switch':
                $fieldTemplate = get_template('scaffold.fields.toggle-switch'.$localized, $templateType);
                break;
        }

        return $fieldTemplate;
    }
}
