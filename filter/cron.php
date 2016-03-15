<?php

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

// Load configuration
require_once(CONFIG_FILE);


// Run application
require_once(MODELS_PATH . 'model_mysql_pdo.php');

// Load necessary models/controllers files
require_once(MODELS_PATH . 'model_verify_email_api.php');
require_once(CONTROLLERS_PATH . 'controller_file_handler.php');

// Establish datbase connection
$mysql = new Model_Mysql;
$dbh = $mysql->db_connect();
$file = new File_Handler($dbh);

$restFiles = $file->check_uncompleted();

if (!count($restFiles)) {
    exit;
}

$sourcePath = SOURCE_PATH . '/' . array_values($restFiles)[0];

try {
    $sourceArray = $file->explode_source($sourcePath);
} 
catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Extract email addresses from source file
try {
    $numRest = $file->extract_emails($sourceArray);
} 
catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

$filePath = FILES_PATH . '/' . $file->file_name;
$fileId = $file->db_record_file($filePath);

if ($f_added_num = $file->db_record_emails($fileId)) {
    $file->record_file_log($fileId, $f_added_num);
}

if (!$numRest) {
    echo 'email filter complete';
    exit;
}

?>
