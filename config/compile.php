<?php

$__rpdir = realpath(__DIR__ . '/../');

return [

	'files' => [
		realpath(__DIR__.'/../app/Providers/AppServiceProvider.php'),
		realpath(__DIR__.'/../app/Providers/BusServiceProvider.php'),
		realpath(__DIR__.'/../app/Providers/ConfigServiceProvider.php'),
		realpath(__DIR__.'/../app/Providers/EventServiceProvider.php'),
		realpath(__DIR__.'/../app/Providers/RouteServiceProvider.php'),
		realpath(__DIR__.'/../app/Http/Controllers/RestController.php'),
		realpath(__DIR__.'/../app/Http/Controllers/SplashController.php'),
		realpath(__DIR__.'/../app/Http/Controllers/StorageController.php'),
		realpath(__DIR__.'/../app/Http/Middleware/AccessCheck.php'),
		realpath(__DIR__.'/../app/Http/Middleware/Cors.php'),
		realpath(__DIR__.'/../app/Http/Middleware/FirstUserCheck.php'),
	],

    /*
    |--------------------------------------------------------------------------
    | Compiled File Providers
    |--------------------------------------------------------------------------
    |
    | Here you may list service providers which define a "compiles" function
    | that returns additional files that should be compiled, providing an
    | easy way to get common files from any packages you are utilizing.
    |
    */

    'providers' => [
        //
    ],

];
