<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

use DreamFactory\Managed\Support\Managed;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;

$app = new Illuminate\Foundation\Application(realpath(__DIR__ . '/../'));

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton('Illuminate\Contracts\Http\Kernel',
    'DreamFactory\Http\Kernel');

$app->singleton('Illuminate\Contracts\Console\Kernel',
    'DreamFactory\Console\Kernel');

$app->singleton('Illuminate\Contracts\Debug\ExceptionHandler',
    'DreamFactory\Exceptions\Handler');

$app->configureMonologUsing(function ($monolog) {
    $logFile = config('df.standalone') ? Managed::getLogFile() : storage_path('logs/dreamfactory.log');
    $mode = config('app.log');

    $_handler = null;
    $_formatter = new LineFormatter(null, null, true, true);

    /** @type Monolog\Logger $monolog */
    switch ($mode) {
        case 'syslog':
            $_handler = new SyslogHandler('dreamfactory');
            $_formatter = null;
            break;

        case 'single':
            $_handler = new StreamHandler($logFile);
            break;

        case 'errorlog':
            $_handler = new ErrorLogHandler();
            break;

        default:
            $_handler = new RotatingFileHandler($logFile, 5);
            break;
    }

    $_handler && $monolog->pushHandler($_handler);
    $_formatter && $_handler->setFormatter($_formatter);
});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
