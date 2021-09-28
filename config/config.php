<?php

use Laraneat\Modules\Activators\FileActivator;
use Laraneat\Modules\Commands;

return [
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | The path to assets
        |--------------------------------------------------------------------------
        |
        | This path is used to store public assets of modules
        |
        */
        'assets' => public_path('modules'),
    ],

    'generator' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path used for save the generated module. This path also will be added
        | automatically to list of scanned folders.
        |
        */
        'path' => base_path('app/Modules'),

        /*
        |--------------------------------------------------------------------------
        | Default Module Namespace
        |--------------------------------------------------------------------------
        |
        | Default module namespace.
        |
        */
        'namespace' => 'App\\Modules',

        /*
        |--------------------------------------------------------------------------
        | Custom generator stubs path
        |--------------------------------------------------------------------------
        |
        | Place your custom stubs in this folder
        |
        */
        'custom_stubs' => base_path('app/Ship/Generators/custom-stubs'),

        /*
        |--------------------------------------------------------------------------
        | User model class
        |--------------------------------------------------------------------------
        |
        | Customize the User model of the application
        |
        */
        'user_model' => null,

        /*
        |--------------------------------------------------------------------------
        | Create permission action class
        |--------------------------------------------------------------------------
        |
        | Customize "create permission" action class
        |
        */
        'create_permission_action' => null,

        /*
        |--------------------------------------------------------------------------
        | Component paths
        |--------------------------------------------------------------------------
        |
        | Customize the paths where the folders will be generated.
        | Set the generate key to `false` to not generate that folder when creating
        | a module
        |
        */
        'components' => [
            'action' => [
                'path' => 'Actions',
                'generate' => true
            ],
            'api-controller' => [
                'path' => 'UI/API/Controllers',
                'generate' => false
            ],
            'api-query-wizard' => [
                'path' => 'UI/API/QueryWizards',
                'generate' => true
            ],
            'api-request' => [
                'path' => 'UI/API/Requests',
                'generate' => true
            ],
            'api-resource' => [
                'path' => 'UI/API/Resources',
                'generate' => true
            ],
            'api-route' => [
                'path' => 'UI/API/Routes',
                'generate' => true
            ],
            'api-test' => [
                'path' => 'UI/API/Tests',
                'generate' => true
            ],
            'cli-command' => [
                'path' => 'UI/CLI/Commands',
                'generate' => false
            ],
            'cli-test' => [
                'path' => 'UI/CLI/Tests',
                'generate' => false
            ],
            'config' => [
                'path' => 'Config',
                'generate' => true
            ],
            'event' => [
                'path' => 'Events',
                'generate' => false
            ],
            'exception' => [
                'path' => 'Exceptions',
                'generate' => false
            ],
            'factory' => [
                'path' => 'Data/Factories',
                'generate' => true
            ],
            'feature-test' => [
                'path' => 'Tests/Feature',
                'generate' => false
            ],
            'job' => [
                'path' => 'Jobs',
                'generate' => false
            ],
            'lang' => [
                'path' => 'Resources/lang',
                'generate' => false
            ],
            'listener' => [
                'path' => 'Listeners',
                'generate' => false
            ],
            'mail' => [
                'path' => 'Mails',
                'generate' => false
            ],
            'middleware' => [
                'path' => 'Middleware',
                'generate' => false
            ],
            'migration' => [
                'path' => 'Data/Migrations',
                'generate' => true
            ],
            'model' => [
                'path' => 'Models',
                'generate' => true
            ],
            'notification' => [
                'path' => 'Notifications',
                'generate' => false
            ],
            'observer' => [
                'path' => 'Observers',
                'generate' => false
            ],
            'policy' => [
                'path' => 'Policies',
                'generate' => true
            ],
            'provider' => [
                'path' => 'Providers',
                'generate' => true
            ],
            'rule' => [
                'path' => 'Rules',
                'generate' => false
            ],
            'seeder' => [
                'path' => 'Data/Seeders',
                'generate' => true
            ],
            'web-controller' => [
                'path' => 'UI/WEB/Controllers',
                'generate' => false
            ],
            'web-request' => [
                'path' => 'UI/WEB/Requests',
                'generate' => false,
            ],
            'web-route' => [
                'path' => 'UI/WEB/Routes',
                'generate' => false
            ],
            'web-test' => [
                'path' => 'UI/WEB/Tests',
                'generate' => false
            ],
            'view' => [
                'path' => 'Resources/views',
                'generate' => false
            ],
            'unit-test' => [
                'path' => 'Tests/Unit',
                'generate' => false
            ],
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. If for example you don't use some of the commands provided
    | you can simply comment them out.
    |
    */
    'commands' => [
        Commands\CacheClearCommand::class,
        Commands\CacheCommand::class,
        Commands\DisableCommand::class,
        Commands\DumpCommand::class,
        Commands\EnableCommand::class,
        Commands\InstallCommand::class,
        Commands\ListCommand::class,
        Commands\MigrateCommand::class,
        Commands\MigrateRefreshCommand::class,
        Commands\MigrateResetCommand::class,
        Commands\MigrateRollbackCommand::class,
        Commands\MigrateStatusCommand::class,
        Commands\ModuleDeleteCommand::class,
        Commands\SeedCommand::class,
        Commands\SetupCommand::class,
        Commands\UnUseCommand::class,
        Commands\UpdateCommand::class,
        Commands\UseCommand::class,
        Commands\Generators\ActionMakeCommand::class,
        Commands\Generators\CommandMakeCommand::class,
        Commands\Generators\ControllerMakeCommand::class,
        Commands\Generators\EventMakeCommand::class,
        Commands\Generators\ExceptionMakeCommand::class,
        Commands\Generators\FactoryMakeCommand::class,
        Commands\Generators\JobMakeCommand::class,
        Commands\Generators\ListenerMakeCommand::class,
        Commands\Generators\MailMakeCommand::class,
        Commands\Generators\MiddlewareMakeCommand::class,
        Commands\Generators\MigrationMakeCommand::class,
        Commands\Generators\ModelMakeCommand::class,
        Commands\Generators\ModuleMakeCommand::class,
        Commands\Generators\NotificationMakeCommand::class,
        Commands\Generators\ObserverMakeCommand::class,
        Commands\Generators\PolicyMakeCommand::class,
        Commands\Generators\ProviderMakeCommand::class,
        Commands\Generators\RouteMakeCommand::class,
        Commands\Generators\QueryWizardMakeCommand::class,
        Commands\Generators\RequestMakeCommand::class,
        Commands\Generators\ResourceMakeCommand::class,
        Commands\Generators\RuleMakeCommand::class,
        Commands\Generators\SeederMakeCommand::class,
        Commands\Generators\TestMakeCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */
    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for composer.json file, generated by this package
    |
    */
    'composer' => [
        'vendor' => 'laraneat',
        'author' => [
            'name' => 'Salakhutdinov Salavat',
            'email' => 'salahutdinov.salavat@gmail.com',
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up caching feature for scanned modules.
    |
    */
    'cache' => [
        'enabled' => env('APP_ENV', 'production') === 'production',
        'key' => 'laraneat.modules',
        'lifetime' => 86400, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Choose what laraneat/modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |--------------------------------------------------------------------------
    */
    'register' => [
        /**
         * load files on boot or register method
         *
         * @example boot|register
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */
    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'laraneat.activator.installed',
            'cache-lifetime' => 604800, // 7 days
        ],
    ],

    'activator' => 'file',
];
