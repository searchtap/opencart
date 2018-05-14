<?php
// CLI must be called by cli php
if (php_sapi_name() != 'cli') {
    syslog(LOG_ERR, "cli $cli_action call attempted by non-cli.");
    http_response_code(400);
    exit;
}

// Ensure $cli_action is set
if (!isset($cli_action)) {
    echo 'ERROR: $cli_action must be set in calling script.';
    syslog(LOG_ERR, '$cli_action must be set in calling script');
    http_response_code(400);
    exit;
}

// Handle errors by writing to log
function cli_error_handler($log_level, $log_text, $error_file, $error_line) {
    syslog(LOG_ERR, 'CLI Error: ' . $log_text . ' in ' . $error_file . ': ' . $error_line);
    echo 'CLI Error: ' . $log_text . ' in ' . $error_file . ': ' . $error_line;
}
set_error_handler('cli_error_handler');

// Configuration (note we're using the admin config)
require_once __DIR__.('/../admin/config.php');


// Configuration not present in CLI (vs web)
chdir(__DIR__.'/../admin');
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '../admin/');
$_SERVER['HTTP_HOST'] = '';



if (!defined('DIR_APPLICATION')) {
    echo "ERROR: cli $cli_action call missing configuration.";
    http_response_code(400);
    exit;
}

// Version
define('VERSION', '2.3.0.3_rc');

// Configuration
if (is_file('config.php')) {
    require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
    header('Location: ../install/index.php');
    exit;
}

////VirtualQMOD
//require_once('../vqmod/vqmod.php');
//VQMod::bootup();

// VQMODDED Startup
require_once __DIR__.('/../system/startup.php');

start('cli');