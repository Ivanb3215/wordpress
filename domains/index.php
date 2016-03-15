<?php
session_start();

/**
 * Directory in which your configuration file is located.
 * o no trailing slash.
 * 
 * For security reason, it's better to locate this folder
 * outside the server public directory.
 */
define('CONFIG_DIR', 'config');

/**
 * Configuration file path/name
 */
define('CONFIG_FILE', CONFIG_DIR . '/config.php');

/**
 * Directory in which your application specific resources are located.
 * o No trailing slash.
 */
define('APPLICATION_DIR', 'application');

// Set full path to document root
define('DOC_ROOT', str_replace('\\', '/', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR));
//define('DOC_ROOT', '/home3/spotim/public_html/domains'  . DIRECTORY_SEPARATOR);

require_once CONFIG_FILE;

// Run application
require_once(MODELS_PATH . 'model_mysql_pdo.php');
require_once(CONTROLLERS_PATH . 'controller_router.php');

new Router;

exit;
?>
