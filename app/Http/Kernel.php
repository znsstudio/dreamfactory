<?php namespace DreamFactory\Http;

use DreamFactory\Http\Middleware\AccessCheck;
use DreamFactory\Http\Middleware\Cors;
use DreamFactory\Http\Middleware\FirstUserCheck;
use Dreamfactory\Managed\Http\Middleware\DataCollection;
use Dreamfactory\Managed\Http\Middleware\Limits;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        FirstUserCheck::class,
        Limits::class,
        DataCollection::class,
        Cors::class,
    ];

    /** @inheritdoc */
    protected $routeMiddleware = [
        'auth.basic'   => AuthenticateWithBasicAuth::class,
        'access_check' => AccessCheck::class,
    ];
}
