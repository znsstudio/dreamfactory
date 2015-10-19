<?php namespace DreamFactory\Http\Middleware;

use Closure;
use DreamFactory\Core\Enums\VerbsMask;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ForbiddenException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Models\App;
use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\User\Services\User;
use DreamFactory\Core\Utility\JWTUtilities;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Managed\Enums\ManagedDefaults;
use DreamFactory\Managed\Support\Managed;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Payload;

class AccessCheck
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array
     */
    protected static $exceptions = [
        [
            'verb_mask' => 31, //   Allow all verbs
            'service'   => 'system',
            'resource'  => 'admin/session',
        ],
        [
            'verb_mask' => 31, //   Allow all verbs
            'service'   => 'user',
            'resource'  => 'session',
        ],
        [
            'verb_mask' => 2, //    Allow POST only
            'service'   => 'user',
            'resource'  => 'password',
        ],
        [
            'verb_mask' => 2, //    Allow POST only
            'service'   => 'system',
            'resource'  => 'admin/password',
        ],
        [
            'verb_mask' => 1,
            'service'   => 'system',
            'resource'  => 'environment',
        ],
        [
            'verb_mask' => 1,
            'service'   => 'user',
            'resource'  => 'profile',
        ],
    ];

    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return array|mixed|string
     */
    public function handle($request, Closure $next)
    {
        $_validSession = false;

        try {
            static::setExceptions();

            //  Look for, and set, an API key
            Session::setApiKey($_apiKey = static::getApiKey($request));

            //  Get the app id
            $_appId = App::getAppIdByApiKey($_apiKey);

            //  Look for, and set, a JWT
            Session::setSessionToken($_token = static::getJwt($request));

            //  Set the session data
            if (!$this->isManagedRequest($request)) {
                $_validSession = $this->setSessionData($request, $_token, $_apiKey, $_appId);
            }

            //  Send the request through
            if (static::isAccessAllowed() || !$_validSession) {
                return $next($request);
            }

            if (!Session::isAuthenticated()) {
                throw new UnauthorizedException('Unauthorized.');
            }

            throw new ForbiddenException('Access Forbidden.');
        } catch (\Exception $e) {
            return ResponseFactory::getException($e, $request);
        }
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public static function getApiKey($request)
    {
        //  Check for API key in request parameters or HTTP headers if not there
        return $request->query('api_key', $request->header('X_DREAMFACTORY_API_KEY'));
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public static function getConsoleApiKey(Request $request)
    {
        if (config('df.standalone')) {
            return null;
        }

        //  Check for Console API key in request parameters and the HTTP headers
        return $request->query('console_key', $request->header(ManagedDefaults::CONSOLE_X_HEADER));
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public static function getJwt($request)
    {
        return static::getJWTFromAuthHeader()
            ?: $request->header('X_DREAMFACTORY_SESSION_TOKEN',
                $request->input('session_token', $request->input('token')));
    }

    /**
     * Generates the role data array using the role model.
     *
     * @param Role $role
     *
     * @return array
     */
    protected static function getRoleData(Role $role)
    {
        return [
            'name'     => $role->name,
            'id'       => $role->id,
            'services' => $role->getRoleServiceAccess(),
        ];
    }

    /**
     * Checks to see if it is an admin user login call.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\NotImplementedException
     */
    protected static function isException($request)
    {
        $_params = static::getRequestParameters($request);

        foreach (static::$exceptions as $exception) {
            if (($_params['action'] & array_get($exception, 'verb_mask')) &&
                $_params['service'] == array_get($exception, 'service') &&
                $_params['resource'] == array_get($exception, 'resource')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks to see if Access is Allowed based on Role-Service-Access.
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\NotImplementedException
     */
    public static function isAccessAllowed()
    {
        $_params = static::getRequestParameters();

        return $_params['action'] & Session::getServicePermissions($_params['service'], $_params['resource']);
    }

    protected static function setExceptions()
    {
        if (class_exists(User::class)) {
            $userService = Service::getCachedByName('user');

            if ($userService['config']['allow_open_registration']) {
                static::$exceptions[] = [
                    'verb_mask' => 2, //    Allow POST only
                    'service'   => 'user',
                    'resource'  => 'register',
                ];
            }
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string                   $appId
     * @param string|null              $token
     * @param string|null              $apiKey
     *
     * @return bool True if the request is authenticated/valid. False if it's already an error.
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\ForbiddenException
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     */
    protected function setSessionData(Request $request, $appId, $token = null, $apiKey = null)
    {
        //  JWT authenticated
        if (!empty($token)) {
            $this->setSessionDataFromToken($request, $token, $appId);

            return true;
        }

        //  Just Api Key is supplied. No authenticated session
        if (!empty($apiKey)) {
            Session::setSessionData($appId);

            return true;
        }

        //  Basic auth
        if ($request->getUser() && $request->getPassword()) {
            $this->setBasicAuthSession($request);

            return true;
        }

        //  Path exception
        if (static::isException($request)) {
            return false;
        }

        throw new BadRequestException('Bad request. No token or api key provided.');
    }

    /**
     * Set session data from authenticated JWT token
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $token
     * @param string                   $appId
     *
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\ForbiddenException
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     */
    protected function setSessionDataFromToken(Request $request, $token, $appId)
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            JWTAuth::setToken($token);

            /** @noinspection PhpUndefinedMethodInspection */
            $_payload = JWTAuth::getPayload();
            JWTUtilities::verifyUser($_payload);

            /** @type Payload $payload */
            Session::setSessionData($appId, $payload->get('user_id'));
        } catch (TokenExpiredException $e) {
            JWTUtilities::clearAllExpiredTokenMaps();

            if (!static::isException($request)) {
                throw new UnauthorizedException($e->getMessage());
            }
        } catch (TokenBlacklistedException $e) {
            throw new ForbiddenException($e->getMessage());
        } catch (TokenInvalidException $e) {
            throw new BadRequestException('Invalid token supplied.');
        }
    }

    /**
     * @param string $appId
     *
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     */
    protected function setBasicAuthSession($appId)
    {
        \Auth::onceBasic();

        if (\Auth::guest()) {
            throw new UnauthorizedException('Unauthorized. Invalid credentials.');
        }

        Session::setSessionData($appId, \Auth::user()->id);
    }

    /**
     * Gets the token from Authorization header.
     *
     * @return string
     */
    protected static function getJWTFromAuthHeader()
    {
        //  Not available in test mode.
        if ('testing' == env('APP_ENV')) {
            return [];
        }

        if (!function_exists('getallheaders')) {
            function getallheaders()
            {
                if (!is_array($_SERVER)) {
                    return [];
                }

                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] =
                            $value;
                    }
                }

                return $headers;
            }
        }

        $_authHeader = array_get(getallheaders(), 'Authorization');

        return false !== strpos($_authHeader, 'Bearer') ? substr($_authHeader, 7) : null;
    }

    /**
     * Checks if a request is a DFE request
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function isManagedRequest(Request $request)
    {
        //  Check for a matching console api key
        return static::getConsoleApiKey($request) == Managed::getConsoleKey();
    }

    /**
     * Pulls the action, resource, and service parameters from a request
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\NotImplementedException
     */
    protected static function getRequestParameters(Request $request = null)
    {
        /** @type Router $_router */
        $_router = app('router');

        return [
            'action'   => VerbsMask::toNumeric($request ? $request->getMethod() : \Request::getMethod()),
            'resource' => strtolower($_router->input('resource')),
            'service'  => strtolower($_router->input('service')),
        ];
    }
}
