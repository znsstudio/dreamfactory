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

        //  Boot the managed service
        $_app->singleton(ManagedService)

        //  Configure logging
        $_app->configureMonologUsing(function (Logger $monolog){
            $logFile = storage_path('logs/dreamfactory.log');
            if (config('df.managed')) {
                $logFile = Managed::getLogFile();
            }

            $mode = config('app.log');

            if ($mode === 'syslog') {
                $monolog->pushHandler(new SyslogHandler('dreamfactory'));
            } else {
                if ($mode === 'single') {
                    $handler = new StreamHandler($logFile);
                } else if ($mode === 'errorlog') {
                    $handler = new ErrorLogHandler();
                } else {
                    $handler = new RotatingFileHandler($logFile, 5);
                }

                $monolog->pushHandler($handler);
                $handler->setFormatter(new LineFormatter(null, null, true, true));
            }
        });

        //  Return the app
        return $_app;
    }
}

return __df_bootstrap();
