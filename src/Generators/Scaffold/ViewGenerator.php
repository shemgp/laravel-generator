<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\HTMLFieldGenerator;

class ViewGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var array */
    private $htmlFields;

    /** @var array */
    private $hidden_fields;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathViews;
        $this->templateType = config('infyom.laravel_generator.templates', 'adminlte-templates');

        $this->hidden_fields = config('infyom.laravel_generator.options.hidden_fields', []);
    }

    public function generate()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }

        $this->commandData->commandComment("\nGenerating Views...");

        if ($this->commandData->getOption('views')) {
            $viewsToBeGenerated = explode(',', $this->commandData->getOption('views'));

            if (in_array('index', $viewsToBeGenerated)) {
                $this->generateTable();
                $this->generateIndex();
            }

            if (count(array_intersect(['create', 'update'], $viewsToBeGenerated)) > 0) {
                $this->generateFields();
            }

            if (in_array('create', $viewsToBeGenerated)) {
                $this->generateCreate();
            }

            if (in_array('edit', $viewsToBeGenerated)) {
                $this->generateUpdate();
            }

            if (in_array('show', $viewsToBeGenerated)) {
                $this->generateShowFields();
                $this->generateShow();
            }
        } else {
            $this->generateTable();
            $this->generateIndex();
            $this->generateFields();
            $this->generateCreate();
            $this->generateUpdate();
            $this->generateShowFields();
            $this->generateShow();
        }

        $this->commandData->commandComment('Views created: ');
    }

    private function generateTable()
    {
        if ($this->commandData->getAddOn('datatables')) {
            $templateData = $this->generateDataTableBody();
            $this->generateDataTableActions();
        } elseif ($this->commandData->getOption('datagrid')) {
            $templateData = $this->generateDatagridBladeTableBody();
        } else {
            $templateData = $this->generateBladeTableBody();
        }

        FileUtil::createFile($this->path, 'table.blade.php', $templateData);

        $this->commandData->commandInfo('table.blade.php created');
    }

    private function generateDataTableBody()
    {
        $templateData = get_template('scaffold.views.datatable_body', $this->templateType);

        return fill_template($this->commandData->dynamicVars, $templateData);
    }

    private function generateDataTableActions()
    {
        $templateData = get_template('scaffold.views.datatables_actions', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'datatables_actions.blade.php', $templateData);

        $this->commandData->commandInfo('datatables_actions.blade.php created');
    }

    private function generateBladeTableBody()
    {
        $templateData = get_template('scaffold.views.blade_table_body', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELD_HEADERS$', $this->generateTableHeaderFields(), $templateData);

        $cellFieldTemplate = get_template('scaffold.views.table_cell', $this->templateType);

        $tableBodyFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex || in_array($field->name, $this->hidden_fields)) {
                continue;
            }

            $tableBodyFields[] = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $cellFieldTemplate,
                $field
            );
        }

        $tableBodyFields = implode(infy_nl_tab(1, 3), $tableBodyFields);

        return str_replace('$FIELD_BODY$', $tableBodyFields, $templateData);
    }

    private function generateDatagridBladeTableBody()
    {
        $templateData = get_template('scaffold.views.datagrid_blade_table_body', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$SET_COLUMNS$', $this->generateDatagridSetColumnFields(), $templateData);

        return $templateData;
    }

    private function generateDatagridSetColumnFields()
    {
        $setColumnsTemplate = get_template('scaffold.views.datagrid_table_set_columns', $this->templateType);

        $setColumns = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex || in_array($field->name, $this->hidden_fields)) {
                continue;
            }

            $field->isFillable = $field->isFillable ? 'true' : 'false';

            $setColumns[] = $fieldTemplate = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $setColumnsTemplate,
                $field
            );
        }

        return implode(infy_nl_tab(0, 1), $setColumns);
    }

    private function generateTableHeaderFields()
    {
        $headerFieldTemplate = get_template('scaffold.views.table_header', $this->templateType);

        $headerFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex || in_array($field->name, $this->hidden_fields)) {
                continue;
            }
            $headerFields[] = $fieldTemplate = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $headerFieldTemplate,
                $field
            );
        }

        return implode(infy_nl_tab(1, 2), $headerFields);
    }

    private function generateIndex()
    {
        $templateData = get_template('scaffold.views.index', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        if (trim(config('infyom.laravel_generator.default_layout')) != "") {
            $templateData = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $templateData);
        }

        if ($this->commandData->getAddOn('datatables')) {
            $templateData = str_replace('$PAGINATE$', '', $templateData);
        } else {
            $paginate = $this->commandData->getOption('paginate');

            if ($paginate) {
                $paginateTemplate = get_template('scaffold.views.paginate', $this->templateType);

                $paginateTemplate = fill_template($this->commandData->dynamicVars, $paginateTemplate);

                $templateData = str_replace('$PAGINATE$', $paginateTemplate, $templateData);
            } else {
                $templateData = str_replace('$PAGINATE$', '', $templateData);
            }
        }

        FileUtil::createFile($this->path, 'index.blade.php', $templateData);

        $this->commandData->commandInfo('index.blade.php created');
    }

    private function generateFields()
    {
        $this->htmlFields = [];

        $field_folder = 'fields';
        if ($this->commandData->getOption('bootform'))
            $field_folder = 'bootform_fields';

        foreach ($this->commandData->fields as $field) {
            if (!$field->inForm || in_array($field->name, $this->hidden_fields)) {
                continue;
            }

            $fieldTemplate = HTMLFieldGenerator::generateHTML($field, $this->templateType, $field_folder);

            if (!empty($fieldTemplate)) {
                $fieldTemplate = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $fieldTemplate,
                    $field
                );
                $this->htmlFields[] = $fieldTemplate;
            }
        }

        $templateData = get_template('scaffold.views.fields', $this->templateType);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELDS$', implode("\n\n", $this->htmlFields), $templateData);

        FileUtil::createFile($this->path, 'fields.blade.php', $templateData);
        $this->commandData->commandInfo('field.blade.php created');
    }

    private function generateCreate()
    {
        $prefix = '';
        if ($this->commandData->getOption('bootform'))
            $prefix = 'bootform_';

        $templateData = get_template('scaffold.views.'.$prefix.'create', $this->templateType);

        if (trim(config('infyom.laravel_generator.default_layout')) != "") {
            $templateData = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $templateData);
        }
        if ($this->commandData->getOption('useJsValidation'))
        {
            $requestClass = $this->commandData->config->nsRequest.'\\Create'.$this->commandData->modelName.'Request';
            $bladeSnippet = "{!! JsValidator::formRequest('$requestClass') !!}";
            $templateData = str_replace('$JS_VALIDATION$', $bladeSnippet, $templateData);
        }
        else
        {
            $templateData = str_replace('$JS_VALIDATION$', '', $templateData);
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'create.blade.php', $templateData);
        $this->commandData->commandInfo('create.blade.php created');
    }

    private function generateUpdate()
    {
        $prefix = '';
        if ($this->commandData->getOption('bootform'))
            $prefix = 'bootform_';

        $templateData = get_template('scaffold.views.'.$prefix.'edit', $this->templateType);

        if (trim(config('infyom.laravel_generator.default_layout')) != "") {
            $templateData = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $templateData);
        }
        if ($this->commandData->getOption('useJsValidation'))
        {
            $requestClass = $this->commandData->config->nsRequest.'\\Update'.$this->commandData->modelName.'Request';
            $bladeSnippet = "{!! JsValidator::formRequest('$requestClass') !!}";
            $templateData = str_replace('$JS_VALIDATION$', $bladeSnippet, $templateData);
        }
        else
        {
            $templateData = str_replace('$JS_VALIDATION$', '', $templateData);
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'edit.blade.php', $templateData);
        $this->commandData->commandInfo('edit.blade.php created');
    }

    private function generateShowFields()
    {
        $fieldTemplate = get_template('scaffold.views.show_field', $this->templateType);

        $fieldsStr = '';

        foreach ($this->commandData->fields as $field) {
            if (!$field->inView) {
                continue;
            }
            $singleFieldStr = str_replace('$FIELD_NAME_TITLE$', Str::title(str_replace('_', ' ', $field->name)),
                $fieldTemplate);
            $singleFieldStr = str_replace('$FIELD_NAME$', $field->name, $singleFieldStr);
            $singleFieldStr = fill_template($this->commandData->dynamicVars, $singleFieldStr);

            $fieldsStr .= $singleFieldStr."\n\n";
        }

        FileUtil::createFile($this->path, 'show_fields.blade.php', $fieldsStr);
        $this->commandData->commandInfo('show_fields.blade.php created');
    }

    private function generateShow()
    {
        $templateData = get_template('scaffold.views.show', $this->templateType);

        if (trim(config('infyom.laravel_generator.default_layout')) != "") {
            $templateData = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $templateData);
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'show.blade.php', $templateData);
        $this->commandData->commandInfo('show.blade.php created');
    }

    public function rollback()
    {
        $files = [
            'table.blade.php',
            'index.blade.php',
            'fields.blade.php',
            'create.blade.php',
            'edit.blade.php',
            'show.blade.php',
            'show_fields.blade.php',
        ];

        if ($this->commandData->getAddOn('datatables')) {
            $files[] = 'datatables_actions.blade.php';
        }

        foreach ($files as $file) {
            if ($this->rollbackFile($this->path, $file)) {
                $this->commandData->commandComment($file.' file deleted');
            }
        }
    }
}
