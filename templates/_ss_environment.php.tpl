<?php

//
// SilverStripe config
//

define('SS_ENVIRONMENT_TYPE', '{{environment}}');


// Database settings
define('SS_DATABASE_SERVER', '{{db_host}}');
define('SS_DATABASE_USERNAME', '{{db_user}}');
define('SS_DATABASE_PASSWORD', '{{db_password}}');
//define('SS_DATABASE_NAME', 'db');


// Default CMS admin user
//define('SS_DATABASE_PREFIX', 'db_prefix');
//define('SS_DATABASE_SUFFIX', 'db_suffix');

// Other settings
//define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
//define('SS_DEFAULT_ADMIN_PASSWORD', 'password');


// SS Log
define('SS_ERROR_LOG', '../{{base_url}}.log');


// File-to-url mapping
//global $_FILE_TO_URL_MAPPING;
//$_FILE_TO_URL_MAPPING['/path/to/release'] = 'http://{{base_url}}';


// Include an additional _secrets.php file where needed
if(file_exists(dirname(__DIR__) . "/_secrets.php")) {
    include(dirname(__DIR__) . "/_secrets.php");
}
