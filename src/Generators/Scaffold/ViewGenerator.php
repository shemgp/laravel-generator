<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Generators\ViewServiceProviderGenerator;
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

        $htmlInputs = Arr::pluck($this->commandData->fields, 'htmlInput');
        if (in_array('file', $htmlInputs)) {
            $this->commandData->addDynamicVariable('$FILES$', ", 'files' => true");
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
        $templateName = 'datatables_actions';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'datatables_actions.blade.php', $templateData);

        $this->commandData->commandInfo('datatables_actions.blade.php created');
    }

    private function generateBladeTableBody()
    {
        $templateName = 'blade_table_body';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);

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

            $this->commandData->dynamicVars['$DATAGRID_TABLE_FK_NAME$'] = null;
            if (preg_match("/(.*)(_id)$/", $field->name, $matches))
            {
                $pdo = \DB::getPdo();
                $current_driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

                // get shcema
                $tmp_table = array_reverse(explode(".", $this->commandData->dynamicVars['$TABLE_NAME$']));
                $table = $tmp_table[0];
                $schema = $tmp_table[1]??'public';

                // get db connection from column table name
                $found = false;
                $column_table = $matches[1];
                $singular_name = Str::singular($column_table);
                $plural_name = Str::plural($singular_name);
                $db_connections = array_keys(config('database.connections'));
                $connection = null;
                foreach($db_connections as $db_connection)
                {
                    $db_connection_data = config('database.connections')[$db_connection];
                    if ($db_connection_data['driver'] != $current_driver
                            && $db_connection_data['schema'] != $schema)
                        continue;
                    try {
                        \DB::connection($db_connection)->getPdo();
                    } catch (\Exception $e) {
                        continue;
                    }
                    if ($db_connection)
                    {
                        if (\Schema::connection($db_connection)->hasTable($plural_name)) {
                            $found_table = $plural_name;
                            $connection = $db_connection;
                            break;
                        } else if (\Schema::connection($db_connection)->hasTable($singular_name)) {
                            $found_table = $singular_name;
                            $connection = $db_connection;
                            break;
                        }
                    }
                }

                // find primary key
                $indexes = \DB::connection($connection)->getDoctrineSchemaManager()->listTableIndexes($found_table);
                $primary_key = 'id';
                foreach($indexes as $type => $index)
                {
                    if ($type == 'primary')
                    {
                        $columns = $index->getColumns();
                        if (count($columns) == 1)
                            $primary_key = $columns[0];
                        break;
                    }
                }

                // guess name by getting first text if "name" field doesn't exist
                $default_name = 'name';
                $field_name = null;
                if (!\Schema::connection($connection)->hasColumn($found_table, $default_name))
                {
                    $columns = \DB::connection($connection)->getDoctrineSchemaManager()->listTableColumns($found_table);
                    foreach($columns as $column)
                    {
                        if ($column->getType()->getName() == "text")
                        {
                            $field_name = $column->getName();
                        }
                    }
                }
                else {
                    $field_name = $default_name;
                }

                $wrapperTempTopTemplate = <<<'EOF'

                        'wrapper' => function ($value, $row) {
                            $db = \DB::connection("$CONNECTION$")
                                ->table("$TABLE$")
                                ->where("$PRIMARY_KEY$", $value);
                EOF;
                if ($field_name)
                {
                    $wrapperTempMiddleTemplate = <<<'EOF'

                                return $db->get("$NAME$")[0]->{"$NAME$"};
                    EOF;
                    $wrapperTempMiddleTemplate = fill_template(['$NAME$' => $field_name], $wrapperTempMiddleTemplate);
                }
                else
                {
                    $wrapperTempMiddleTemplate = <<<'EOF'

                                return $value;
                    EOF;
                }
                $wrapperTempBottomTemplate = <<<'EOF'

                        }
                EOF;

                $wrapperTempTemplate = fill_template(
                    [
                        '$CONNECTION$' => $connection,
                        '$PRIMARY_KEY$' => $primary_key,
                        '$TABLE$' => $found_table
                    ],
                    $wrapperTempTopTemplate.$wrapperTempMiddleTemplate.$wrapperTempBottomTemplate,
                );
                $withDataTableTemplate = fill_template(['$DATAGRID_TABLE_FK_NAME$' => $wrapperTempTemplate], $setColumnsTemplate);
            }
            else
            {
                $withDataTableTemplate = $setColumnsTemplate;
            }

            $field->isFillable = $field->isFillable ? 'true' : 'false';

            $setColumns[] = $fieldTemplate = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $withDataTableTemplate,
                $field
            );
        }

        return implode(infy_nl_tab(0, 1), $setColumns);
    }

    private function generateTableHeaderFields()
    {
        $templateName = 'table_header';

        $localized = false;
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
            $localized = true;
        }

        $headerFieldTemplate = get_template('scaffold.views.'.$templateName, $this->templateType);

        $headerFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex || in_array($field->name, $this->hidden_fields)) {
                continue;
            }

            if ($localized) {
                $headerFields[] = $fieldTemplate = fill_template_with_field_data_locale(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $headerFieldTemplate,
                    $field
                );
            } else {
                $headerFields[] = $fieldTemplate = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $headerFieldTemplate,
                    $field
                );
            }
        }

        return implode(infy_nl_tab(1, 2), $headerFields);
    }

    private function generateIndex()
    {
        $templateName = 'index';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);

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
        $templateName = 'fields';

        $localized = false;
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
            $localized = true;
        }

        $this->htmlFields = [];

        $field_folder = 'fields';
        if ($this->commandData->getOption('bootform'))
            $field_folder = 'bootform_fields';

        foreach ($this->commandData->fields as $field) {
            if (!$field->inForm || in_array($field->name, $this->hidden_fields)) {
                continue;
            }

            $validations = explode('|', $field->validations);
            $minMaxRules = '';
            foreach ($validations as $validation) {
                if (!Str::contains($validation, ['max:', 'min:'])) {
                    continue;
                }

                $validationText = substr($validation, 0, 3);
                $sizeInNumber = substr($validation, 4);

                $sizeText = ($validationText == 'min') ? 'minlength' : 'maxlength';
                if ($field->htmlType == 'number') {
                    $sizeText = $validationText;
                }

                $size = ",'$sizeText' => $sizeInNumber";
                $minMaxRules .= $size;
            }
            $this->commandData->addDynamicVariable('$SIZE$', $minMaxRules);

            $fieldTemplate = HTMLFieldGenerator::generateHTML($field, $this->templateType, $localized, $field_folder);

            if ($field->htmlType == 'selectTable') {
                $inputArr = explode(',', $field->htmlValues[1]);
                $columns = '';
                foreach ($inputArr as $item) {
                    $columns .= "'$item'".',';  //e.g 'email,id,'
                }
                $columns = substr_replace($columns, '', -1); // remove last ,

                $htmlValues = explode(',', $field->htmlValues[0]);
                $selectTable = $htmlValues[0];
                $modalName = null;
                if (count($htmlValues) == 2) {
                    $modalName = $htmlValues[1];
                }

                $tableName = $this->commandData->config->tableName;
                $variableName = Str::singular($selectTable).'Items'; // e.g $userItems

                $fieldTemplate = $this->generateViewComposer($tableName, $variableName, $columns, $selectTable, $modalName);
            }

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

        $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELDS$', implode("\n\n", $this->htmlFields), $templateData);

        FileUtil::createFile($this->path, 'fields.blade.php', $templateData);
        $this->commandData->commandInfo('field.blade.php created');
    }

    private function generateViewComposer($tableName, $variableName, $columns, $selectTable, $modelName = null)
    {
        $fieldTemplate = get_template('scaffold.fields.select', $this->templateType);

        $viewServiceProvider = new ViewServiceProviderGenerator($this->commandData);
        $viewServiceProvider->generate();
        $viewServiceProvider->addViewVariables($tableName.'.fields', $variableName, $columns, $selectTable, $modelName);

        $fieldTemplate = str_replace(
            '$INPUT_ARR$',
            '$'.$variableName,
            $fieldTemplate
        );

        return $fieldTemplate;
    }

    private function generateCreate()
    {
        $templateName = 'create';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $prefix = '';
        if ($this->commandData->getOption('bootform'))
            $prefix = 'bootform_';

        $templateData = get_template('scaffold.views.'.$prefix.$templateName, $this->templateType);

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
        $templateName = 'edit';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }


        $prefix = '';
        if ($this->commandData->getOption('bootform'))
            $prefix = 'bootform_';

        $templateData = get_template('scaffold.views.'.$prefix.$templateName, $this->templateType);

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
        $templateName = 'show_field';
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }
        $fieldTemplate = get_template('scaffold.views.'.$templateName, $this->templateType);

        $fieldsStr = '';

        foreach ($this->commandData->fields as $field) {
            if (!$field->inView) {
                continue;
            }
            $singleFieldStr = str_replace(
                '$FIELD_NAME_TITLE$',
                Str::title(str_replace('_', ' ', $field->name)),
                $fieldTemplate
            );
            $singleFieldStr = str_replace('$FIELD_NAME$', $field->name, $singleFieldStr);
            $singleFieldStr = fill_template($this->commandData->dynamicVars, $singleFieldStr);

            $fieldsStr .= $singleFieldStr."\n\n";
        }

        FileUtil::createFile($this->path, 'show_fields.blade.php', $fieldsStr);
        $this->commandData->commandInfo('show_fields.blade.php created');
    }

    private function generateShow()
    {
        $templateName = 'show';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);

        if (trim(config('infyom.laravel_generator.default_layout')) != "") {
            $templateData = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $templateData);
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'show.blade.php', $templateData);
        $this->commandData->commandInfo('show.blade.php created');
    }

    public function rollback($views = [])
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

        if (!empty($views)) {
            $files = [];
            foreach ($views as $view) {
                $files[] = $view.'.blade.php';
            }
        }

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
