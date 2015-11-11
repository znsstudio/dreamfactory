<?php namespace DreamFactory\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $namespace = 'DreamFactory\Http\Controllers';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router)
    {
        $router->group(['namespace' => $this->namespace],
            function (Router $router){
                /** @noinspection PhpIncludeInspection */
                require app_path('Http/routes.php');
            });
    }
}
