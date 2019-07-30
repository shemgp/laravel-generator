<?php
/*
|------------------------------------------------------------------------------
| Package - fill the following when generating for a packate
|------------------------------------------------------------------------------
|
*/
$package_path = ''; // eg. 'packages/shemgp/rbac_manager/src/'
$package_namespace = ''; // eg. 'shemgp\\rbac_manager\\'
$package_view_name = ''; // eg. 'rbac_manager::'

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    */

    'path' => [

        'migration'         => base_path($package_path.'database/migrations/'),

        'model'             => base_path($package_path.'app/Models/'),

        'datatables'        => base_path($package_path.'app/DataTables/'),

        'repository'        => base_path($package_path.'app/Repositories/'),

        'routes'            => base_path($package_path.'routes/web.php'),

        'api_routes'        => base_path($package_path.'routes/api.php'),

        'request'           => base_path($package_path.'app/Http/Requests/'),

        'api_request'       => base_path($package_path.'app/Http/Requests/API/'),

        'controller'        => base_path($package_path.'app/Http/Controllers/'),

        'api_controller'    => base_path($package_path.'app/Http/Controllers/API/'),

        'test_trait'        => base_path($package_path.'tests/traits/'),

        'repository_test'   => base_path($package_path.'tests/'),

        'api_test'          => base_path($package_path.'tests/'),

        'views'             => base_path($package_path.'resources/views/'),

        'schema_files'      => base_path($package_path.'resources/model_schemas/'),

        'templates_dir'     => base_path($package_path.'resources/infyom/infyom-generator-templates/'),

        'modelJs'           => base_path($package_path.'resources/assets/js/models/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    */

    'namespace' => [

        'model'             => $package_namespace.'App\Models',

        'datatables'        => $package_namespace.'App\DataTables',

        'repository'        => $package_namespace.'App\Repositories',

        'controller'        => $package_namespace.'App\Http\Controllers',

        'api_controller'    => $package_namespace.'App\Http\Controllers\API',

        'request'           => $package_namespace.'App\Http\Requests',

        'api_request'       => $package_namespace.'App\Http\Requests\API',
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    */

    'templates'         => 'adminlte-templates',

    'default_layout'    => $package_view_name.'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Model extend class
    |--------------------------------------------------------------------------
    |
    */

    'model_extend_class' => env('ENABLE_USER_TRACKING_MODEL') == 'true' ? 'InfyOm\Generator\Model\UserTrackingBaseModel' : 'Eloquent',

    'model_default_date_format' => 'Y-m-d H:i:sO',

    /*
    |--------------------------------------------------------------------------
    | API routes prefix & version
    |--------------------------------------------------------------------------
    |
    */

    'api_prefix'  => 'api',

    'api_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    */

    'options' => [

        'softDelete' => true,

        'tables_searchable_default' => false,

        'hidden_fields' => env('ENABLE_USER_TRACKING_MODEL') == 'true' ? [
            'user_id'
        ] : [],

        'excluded_fields' => [
            'created_at',
            'updated_at',
            'deleted_at',
            'id'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefixes
    |--------------------------------------------------------------------------
    |
    */

    'prefixes' => [

        'route' => '',  // using admin will create route('admin.?.index') type routes

        'path' => '',

        'view' => '',  // using backend will create return view('backend.?.index') type the backend views directory

        'package_view_name' => $package_view_name,

        'public' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-Ons
    |--------------------------------------------------------------------------
    |
    */

    'add_on' => [

        'swagger'       => false,

        'tests'         => true,

        'datatables'    => false,

        'menu'          => [

            'enabled'       => true,

            'menu_file'     => 'layouts/menu.blade.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamp Fields
    |--------------------------------------------------------------------------
    |
    */

    'timestamps' => [

        'enabled'       => true,

        'created_at'    => 'created_at',

        'updated_at'    => 'updated_at',

        'deleted_at'    => 'deleted_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Save model files to `App/Models` when use `--prefix`. see #208
    |--------------------------------------------------------------------------
    |
    */
    'ignore_model_prefix' => false,

];
