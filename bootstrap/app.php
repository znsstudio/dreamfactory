<?php
//******************************************************************************
//* App Bootstrap
//******************************************************************************

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;

if (!function_exists('__dfe_bootstrap')) {
    /**
     * @return \Illuminate\Foundation\Application
     */
    function __df_bootstrap()
    {
        //  Create the app
        $_app = new Illuminate\Foundation\Application(realpath(__DIR__ . '/../'));

        //  Bind default services
        $_app->singleton(Illuminate\Contracts\Http\Kernel::class, DreamFactory\Http\Kernel::class);
        $_app->singleton(Illuminate\Contracts\Console\Kernel::class, DreamFactory\Console\Kernel::class);
        $_app->singleton(Illuminate\Contracts\Debug\ExceptionHandler::class, DreamFactory\Exceptions\Handler::class);

        //  Configure logging
        $_app->configureMonologUsing(function (Logger $monolog){
            $_handler = null;

            $_logPath = env('DF_MANAGED_LOG_PATH', storage_path('logs'));
            $_logFile = $_logPath . DIRECTORY_SEPARATOR . env('DF_MANAGED_LOG_FILE', 'dreamfactory.log');

            switch (config('app.log')) {
                case 'syslog':
                    $monolog->pushHandler(new SyslogHandler('dreamfactory'));
                    break;
                case 'single':
                    $_handler = new StreamHandler($_logFile);
                    break;
                case 'errorlog':
                    $_handler = new ErrorLogHandler($_logFile);
                    break;
                default:
                    $_handler = new RotatingFileHandler($_logFile, env('DF_MANAGED_LOG_ROTATIONS', 5));
                    break;
            }

            $_handler && $_handler->setFormatter(new LineFormatter(null, null, true, true));
        });

        //  Return the app
        return $_app;
    }
}

return __df_bootstrap();
