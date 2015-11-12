<?php
//******************************************************************************
//* Application Autoloader
//******************************************************************************

define('LARAVEL_START', microtime(true));

/** Isolate auto-load code as to not pollute the global namespace */
if (!function_exists('__dfe_autoload')) {
    /**
     * Bootstrap DF
     *
     * @return bool
     */
    function __df_autoload()
    {
        //  Register The Composer Auto Loader
        require __DIR__ . '/../vendor/autoload.php';

        //  Laravel 5.1
        if (file_exists(__DIR__ . '/cache/compiled.php')) {
            /** @noinspection PhpIncludeInspection */
            require __DIR__ . '/cache/compiled.php';
        }

        return true;
    }
}

return __df_autoload();
