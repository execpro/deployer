<?php

// DEV DB SETTINGS
define('SS_DATABASE_SERVER', '{{ db_server_host }}');
define('SS_DATABASE_USERNAME', '{{ db_server_user }}');
define('SS_DATABASE_PASSWORD', '{{ db_server_host }}');

define('SS_ENVIRONMENT_TYPE', '{{ environment }}');

define('SS_DEFAULT_ADMIN_USERNAME', '{{ default_admin_username }}');
define('SS_DEFAULT_ADMIN_PASSWORD', '{{ default_admin_password }}');

define('SS_ERROR_LOG', '../{{ site_name }}.log');
