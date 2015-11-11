<?php namespace DreamFactory\Providers;

use Illuminate\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;

class BusServiceProvider extends ServiceProvider
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Bootstrap any application services.
     *
     * @param  \Illuminate\Bus\Dispatcher $dispatcher
     */
    public function boot(Dispatcher $dispatcher)
    {
        $dispatcher->mapUsing(function ($command){
            return Dispatcher::simpleMapping(
                $command,
                'DreamFactory\Commands',
                'DreamFactory\Handlers\Commands'
            );
        });
    }

    /** @inheritdoc */
    public function register()
    {
        //  No services registered
    }
}
