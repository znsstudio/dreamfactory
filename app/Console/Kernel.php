<?php namespace DreamFactory\Console;

use DreamFactory\Console\Commands\ClearAllFileCache;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $commands = [
        ClearAllFileCache::class,
        //        \DreamFactory\Console\Commands\ClearAllFileCache:class,
        //        \DreamFactory\Console\Commands\Request:class,
        //        \DreamFactory\Console\Commands\Import:class,
        //        \DreamFactory\Console\Commands\ImportPackage:class,
        //        \DreamFactory\Console\Commands\PullMigrations:class,
        //        \DreamFactory\Console\Commands\Setup:class,
    ];
}
