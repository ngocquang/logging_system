<?php
require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('UTC');
// Chú ý set timezone UTC trên server
// Lý do chọn UTC để cho thống nhất trên nhiều server nằm ở nhiều nơi khác nhau.
// Application của bạn thì sẽ cấu hình Timezone cho phù hợp
// Đặc biệt là Log theo thời gian sẽ chính xác hơn.

// Config RabbitMQ
$config['rabbitmq'] = [
  'host' => 'IP_LOG_SERVER',
  'port' => 5672,
  'user' => 'log',
  'pass' => 'XpasswordX',
  'vhost' => '/',
  'queue_sys_log' => 'syslog' // Đây là Queue tùy bạn chọn
];
// Config ClickHouse
$config['clickhouse'] = [
  'host' => 'localhost',
  'port' => '8123',
  'username' => 'default',
  'password' => 'XpasswordX',
  'database' => 'default', // Tên Database tùy bạn chọn
  'log_request_table' => 'log_request', // Log Request từ App
  'log_php_error_table' => 'log_php_error', // Log PHP Error
  'log_syslog_table' => 'log_syslog', // Log hệ thống từ Apache,...
];
