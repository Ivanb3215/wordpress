<?php

/**
 * Make application directory loaction relative to the document root
 * Define application directory path
 */
if (is_dir(DOC_ROOT . APPLICATION_DIR))
    define('APP_PATH', realpath(DOC_ROOT . APPLICATION_DIR) . DIRECTORY_SEPARATOR);
else
    die('Undefined application path');

/**
 * Define directory to upload csv files
 */
define('FILES_PATH', DOC_ROOT . 'files');

/**
 * Define directory to keep source csv files
 */
define('SOURCE_PATH', DOC_ROOT . 'source');

/**
 * Define Byteplant API key
 */
define('BYTEPLANT_API_KEY', 'ev-3a9182b8f5da33e4132a9c149dd72d88');

/**
 * Define Byteplant API url
 */
define('BYTEPLANT_API_URL', 'http://api.email-validator.net/api/verify');

/**
 * Define verify-email.org API username
 */
define('API_USERNAME', '');

/**
 * Define verify-email.org API password
 */
define('API_PASSWORD', '');

/**
 * Define verify-email.org API url
 */
define('API_URL', 'http://api.verify-email.org/api.php?');

/**
 * Define controllers directory path
 */
define('CONTROLLERS_PATH', APP_PATH . 'controllers' . DIRECTORY_SEPARATOR);

/**
 * Define models directory path
 */
define('MODELS_PATH', APP_PATH . 'models' . DIRECTORY_SEPARATOR);

/**
 * Define views directory path
 */
define('VIEWS_PATH', APP_PATH . 'views' . DIRECTORY_SEPARATOR);

/**
 * Define database directory path
 */
define('DATABASE_PATH', APP_PATH . 'database' . DIRECTORY_SEPARATOR);

/**
 * Define css directory path
 */
define('CSS_PATH', VIEWS_PATH . 'css' . DIRECTORY_SEPARATOR);

/**
 * Define relative path (URI) to css directory
 */
define('CSS_URI', 'application/views/css/');

/**
 * Define js directory path
 */
define('JS_PATH', VIEWS_PATH . 'js' . DIRECTORY_SEPARATOR);

/**
 * Define relative path (URI) to js directory
 */
define('JS_URI', 'application/views/js/');

/**
 * Define templates directory path
 */
define('TEMPLATES_PATH', VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR);

/**
 * Define SQL queries directory path
 */
define('QUERIES_PATH', DATABASE_PATH . 'sql_queries' . DIRECTORY_SEPARATOR);

/**
 * Define config directory path
 */
define('CONFIG_PATH', DOC_ROOT . CONFIG_DIR . DIRECTORY_SEPARATOR);

/**
 * Define domain root path
 */
define('DOMAIN_ROOT', '/filter/');
?>