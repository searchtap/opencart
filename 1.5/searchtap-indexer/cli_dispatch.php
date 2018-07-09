<?php


function config() {
// HTTP
//    $dir_path=getcwd();

$dir_path="/var/www/html/current";
    $server_host = str_replace("www.", "", $_SERVER['SERVER_NAME']);

        $url = "https://".$_SERVER['HTTP_HOST']."/current/";
//        $raw_dir_path=getcwd();
    $raw_dir_path = "/var/www/html/current";
        $chunk = explode("/",$raw_dir_path);
        unset($chunk[count($chunk)-1]);
        $dir_path = implode("/",$chunk);

    $dir_path="/var/www/html/current";

    /* HTTP */
    define('HTTP_SERVER', $url.'mothership/');
    define('HTTP_CATALOG', $url);

    /* HTTPS */
    define('HTTPS_SERVER', $url.'mothership/');
    define('HTTPS_CATALOG', $url);

    /* DIR */
    define('DIR_APPLICATION', $dir_path.'/mothership/');
    define('DIR_SYSTEM', $dir_path.'/system/');
    define('DIR_DATABASE', $dir_path.'/system/database/');
    define('DIR_LANGUAGE', $dir_path.'/mothership/language/');
    define('DIR_TEMPLATE', $dir_path.'/mothership/view/template/');
    define('DIR_CONFIG', $dir_path.'/system/config/');
    define('DIR_IMAGE', $dir_path.'/image/');
    define('DIR_CACHE', $dir_path.'/system/cache/');
    define('DIR_DOWNLOAD', $dir_path.'/download/');
    define('DIR_LOGS', $dir_path.'/system/logs/');
    define('DIR_CATALOG', $dir_path.'/catalog/');

    /* DB */
        define('DB_DRIVER', 'mysqli');
        define('DB_HOSTNAME', 'localhost');
        define('DB_USERNAME', 'anirudd_funky');
        define('DB_PASSWORD', 'AVPBFtbjNuBheNQx');
        define('DB_DATABASE', 'anirudd_funky');
        define('DB_PREFIX', 'oc_');

}



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
  //  echo 'CLI Error: ' . $log_text . ' in ' . $error_file . ': ' . $error_line;
}
set_error_handler('cli_error_handler');

// Configuration not present in CLI (vs web)
chdir(__DIR__.'/../mothership');
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '../mothership/');
$_SERVER['HTTP_HOST'] = '';

// Version
define('VERSION', '1.5.1');

// Configuration (note we're using the admin config)
//require_once('../admin/config.php');

config();
// Configuration check
if (!defined('DIR_APPLICATION')) {
    echo "ERROR: cli $cli_action call missing configuration.";
    $log->write("ERROR: cli $cli_action call missing configuration.");
    http_response_code(400);
    exit;
}

//// Startup
//require_once(DIR_SYSTEM . 'startup.php');
//
//// Application Classes
//require_once(DIR_SYSTEM . 'library/currency.php');
//require_once(DIR_SYSTEM . 'library/user.php');
//require_once(DIR_SYSTEM . 'library/weight.php');
//require_once(DIR_SYSTEM . 'library/length.php');



//VirtualQMOD
require_once('../vqmod/vqmod.php');
VQMod::bootup();

// VQMODDED Startup
require_once(DIR_SYSTEM . 'startup_admin.php');

// Application Classes
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/currency.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/user.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/weight.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/length.php'));

require_once(VQMod::modCheck(DIR_SYSTEM . 'engine/controller.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'engine/front.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'engine/loader.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'engine/registry.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/config.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/db.php'));
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/language.php'));


// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

foreach ($query->rows as $setting) {
    if (!$setting['serialized']) {
        $config->set($setting['key'], $setting['value']);
    } else {
        $config->set($setting['key'], unserialize($setting['value']));
    }
}

// Url
$url = new Url(HTTP_SERVER, HTTPS_SERVER);
$registry->set('url', $url);

// Log
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

function error_handler($errno, $errstr, $errfile, $errline) {
    global $log, $config;

    switch ($errno) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error = 'Warning';
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            break;
        default:
            $error = 'Unknown';
            break;
    }

    if ($config->get('config_error_display')) {
        echo "\n".'PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline."\n";
    }

    if ($config->get('config_error_log')) {
        $log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
    }

    return true;
}
set_error_handler('error_handler');
$request = new Request();
$registry->set('request', $request);
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response);
$cache = new Cache();
$registry->set('cache', $cache);
$session = new Session();
$registry->set('session', $session);
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language");
foreach ($query->rows as $result) {
    $languages[$result['code']] = $result;
}
$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);
$language = new Language($languages[$config->get('config_admin_language')]['directory']);
$language->load($languages[$config->get('config_admin_language')]['filename']);
$registry->set('language', $language);

$document = new Document();
$registry->set('document', $document);

$registry->set('currency', new Currency($registry));
$registry->set('weight', new Weight($registry));
$registry->set('length', new Length($registry));
$registry->set('user', new User($registry));

$controller = new Front($registry);
$action = new Action($cli_action);
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();