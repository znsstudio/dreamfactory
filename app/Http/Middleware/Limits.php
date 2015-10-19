<?php namespace Dreamfactory\Http\Middleware;

use Closure;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\TooManyRequestsException;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Managed\Support\Managed;
use Illuminate\Support\Facades\Cache;

class Limits
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type bool
     */
    private $inUnitTest = false;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //Get the Console API Key
        $_apiKey = AccessCheck::getConsoleApiKey($request);

        // Get limits
        if (config('df.standalone', true) || $_apiKey === Managed::getConsoleKey()) {
            return $next($request);
        }

        $_limits = Managed::getLimits();

        //  Convert any \stdClass response into an array
        $_limits['api'] = (array)$_limits['api'];

        foreach (array_keys($_limits['api']) as $_key) {
            $_limits['api'][$_key] = (array)$_limits['api'][$_key];
        }

        if (!empty($_limits) && !is_null($this->getServiceName())) {
            $this->inUnitTest = config('api_limits_test');

            $userName = $this->getUser(Session::getCurrentUserId());
            $userRole = $this->getRole(Session::getRoleId());
            $apiName = $this->getApiKey(Session::getApiKey());
            $serviceName = $this->getServiceName();

            //  Build the list of API Hits to check
            $apiKeysToCheck = ['cluster.default' => 0, 'instance.default' => 0];

            $serviceKeys[$serviceName] = 0;

            if (is_null($userRole) === false) {
                $serviceKeys[$serviceName . '.' . $userRole] = 0;
            }

            if (is_null($userName) === false) {
                $serviceKeys[$serviceName . '.' . $userName] = 0;
            }

            if (is_null($apiName) === false) {
                $apiKeysToCheck[$apiName] = 0;

                if (is_null($userRole) === false) {
                    $apiKeysToCheck[$apiName . '.' . $userRole] = 0;
                }

                if (is_null($userName) === false) {
                    $apiKeysToCheck[$apiName . '.' . $userName] = 0;
                }

                foreach ($serviceKeys as $key => $value) {
                    $apiKeysToCheck[$apiName . '.' . $key] = $value;
                }
            }

            if (is_null($userName) === false) {
                $apiKeysToCheck[$userName] = 0;
            }

            if (is_null($userRole) === false) {
                $apiKeysToCheck[$userRole] = 0;
            }

            $apiKeysToCheck = array_merge($apiKeysToCheck, $serviceKeys);

            $timePeriods = ['minute', 'hour', 'day', '7-day', '30-day'];

            $overLimit = false;

            try {
                foreach (array_keys($apiKeysToCheck) as $key) {
                    foreach ($timePeriods as $period) {
                        $keyToCheck = $key . '.' . $period;

                        if (array_key_exists($keyToCheck, $_limits['api']) === true) {
                            /** @noinspection PhpUndefinedMethodInspection */
                            $cacheValue = Cache::get($keyToCheck, 0);
                            $cacheValue++;
                            /** @noinspection PhpUndefinedMethodInspection */
                            Cache::put($keyToCheck, $cacheValue, $_limits['api'][$keyToCheck]['period']);
                            if ($cacheValue > $_limits['api'][$keyToCheck]['limit']) {
                                $overLimit = true;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return ResponseFactory::getException(new InternalServerErrorException('Unable to update cache'),
                    $request);
            }

            if ($overLimit) {
                return ResponseFactory::getException(new TooManyRequestsException('Specified connection limit exceeded'),
                    $request);
            }
        }

        return $next($request);
    }

    /**
     * Return the User ID from the authenticated session prepended with user_ or null if there is no authenticated user
     *
     * @param $userId
     *
     * @return null|string
     */
    private function getUser($userId)
    {
        return $this->buildCacheKey('user:1', 'user', $userId);
    }

    /**
     * Return the Role ID from the authenticated session prepended with role_ or null if there is no authenticated user
     * or the user has no roles assigned
     *
     * @param $roleId
     *
     * @return null|string
     */
    private function getRole($roleId)
    {
        return $this->buildCacheKey('role:2', 'role', $roleId);
    }

    /**
     * Return the API Key if set or null
     *
     * @param $apiKey
     *
     * @return null|string
     */
    private function getApiKey($apiKey)
    {
        return $this->buildCacheKey('api_key:apiName', 'api_key', $apiKey);
    }

    /**
     * Return the service name.  May return null if a list of services has been requested
     *
     * @return null|string
     */
    private function getServiceName()
    {
        if ($this->inUnitTest) {
            return 'service:serviceName';
        }

        $_service = strtolower(app('router')->input('service'));

        return null === $_service ? null : 'service:' . $_service;
    }

    /**
     * Builds a cache key
     *
     * @param string $key
     * @param string $name
     * @param mixed  $value
     *
     * @return null|string
     */
    private function buildCacheKey($key, $name, $value)
    {
        return $this->inUnitTest ? $key : (null === $value ? null : $name . ':' . $value);
    }
}
