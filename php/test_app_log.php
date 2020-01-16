<?php

// Developement
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Production
/* ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
 */

require_once(__DIR__.'/../vendor/autoload.php');
use SebastianBergmann\Timer\Timer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

Timer::start();
$application_name = 'app-test'; // Application Name
$logger = new Logger($application_name);
$logger->pushHandler(new StreamHandler('/var/log/apache2/my-app/app.log', Logger::DEBUG));

$module_name = basename($_SERVER['PHP_SELF']); // Module Name
$action_name = $_SERVER['REQUEST_METHOD']; // Action
$user_ip = $_SERVER['REMOTE_ADDR']; // IP

// Application Code
$exectime = Timer::stop();

$msg = '[START';
// Info
$msg_item = array();
$msg_item[] = date('Y-m-d'); // Date
$msg_item[] = date("Y-m-d_H:i:s"); // Date Time
$msg_item[] = date("H"); // Hour
$msg_item[] = date("i"); // Minute
$msg_item[] = $application_name; // Application
$msg_item[] = $module_name; // Module
$msg_item[] = $action_name; // Action
$msg_item[] = 'success'; // Status
$msg_item[] = $exectime; // Executive Time
$msg_item[] = \memory_get_peak_usage(true); // Memory Used
$msg_item[] = $user_ip; // User IP address

$msg .= implode(' ',$msg_item);
$msg .= 'END]';
$logger->info($msg);
print Timer::resourceUsage();
echo '<br>'.$msg;
echo '<br>'.'Done';
