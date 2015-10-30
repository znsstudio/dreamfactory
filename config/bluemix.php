<?php
/**
 * DreamFactory(tm) Platform <http://github.com/dreamfactorysoftware/dreamfactory>
 * Copyright 2012-2015 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DreamFactory\Library\Utility\IfSet;
use DreamFactory\Library\Utility\Json;
use Illuminate\Support\Facades\Log;

//*************************************************************************
//	Constants
//*************************************************************************

/**
 * @type string The name of the target PaaS
 */
const PAAS_NAME = 'bluemix';
/**
 * @type string The environment variable that holds the credentials for the database
 */
const ENV_KEY = 'VCAP_SERVICES';
/**
 * @type string The name of the key containing the database
 */
const DB_KEY = 'mysql-5.5';
/**
 * @type int The index of the database to use
 */
const DB_INDEX = 0;
/**
 * @type string The name of the key containing the credentials
 */
const DB_CREDENTIALS_KEY = 'credentials';

//******************************************************************************
//* Main
//******************************************************************************

/**
 * Returns a generic IBM Bluemix database configuration
 * Wrapped to prevent $GLOBAL pollution
 *
 * @return array
 */
return [
    'database' => call_user_func(function () {
        /** @type string $_envData */
        $_envData = getenv(ENV_KEY);

        //  Decode and examine
        try {
            $_services = Json::decode($_envData, true);
        } catch (\InvalidArgumentException $_ex) {
            /** @noinspection PhpUndefinedMethodInspection */
            Log::notice('Environment not set correctly for this deployment.' .
                PHP_EOL .
                'Environment Dump' .
                PHP_EOL .
                '------------------------------------------------------------' .
                PHP_EOL .
                print_r($_ENV, true));

            return false;
        }

        if (!empty($_services)) {
            //  Get credentials environment data
            $_config = array_get(IfSet::getDeep($_services, DB_KEY, DB_INDEX, []), DB_CREDENTIALS_KEY);

            if (empty($_config)) {
                throw new \RuntimeException('DB service not found in services env: ' . print_r($_services, true));
            }

            $_db = [
                'driver'    => 'mysql',
                //  Check for 'host', then 'hostname', default to 'localhost'
                'host'      => array_get($_config, 'host', array_get($_config, 'hostname', 'localhost')),
                'database'  => $_config['name'],
                'username'  => $_config['username'],
                'password'  => $_config['password'],
                'port'      => $_config['port'],
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ];

            unset($_envData, $_config, $_services);

            return $_db;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        Log::notice('Database configuration found for PaaS "' .
            PAAS_NAME .
            '", but required environment variable is invalid or null.' .
            PHP_EOL .
            'Environment Dump' .
            PHP_EOL .
            '------------------------------------------------------------' .
            PHP_EOL .
            print_r($_ENV, true));

        //  Returning false equates to not having a "config/database.config.php"
        return false;
    }),
];
