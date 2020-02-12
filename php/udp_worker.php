<?php
include(__DIR__ . '/config.php');

$limit = 1;
// Để số thấp là để test hoặc traffic bạn thấp,
// còn nếu traffic cao thì nên tăng lên,
// ví dụ 100, để insert một lần vào ClickHouse 100 record
// sẽ có hiệu năng tốc độ insert cao hơn

$current = 0;
$request_logs = array();
$php_error_logs = array();
$syslog_logs = array();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

// Init ClickHouseDB instant
$db = new ClickHouseDB\Client($config['clickhouse']);
$db->database($config['clickhouse']['database']);

$exchange = 'router';
$queue = $config['rabbitmq']['queue_sys_log'];
$consumerTag = 'consumer';

$connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass']);
$channel = $connection->channel();
/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/
/*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue, false, true, false, false);
/*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange);
/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
$callback = function ($message) {
    global  $current,$limit, $db, $config, $request_logs, $php_error_logs, $syslog_logs;

    $msg = $message->body;

    $tmp = explode(' ', $msg);
    if ($msg[0] == "<") { // Đây là Log Apache
        $host = $tmp[3];
    } else {
        $host = $tmp[1]; // Đây là Log App
    }

    // detect request App log
    if (preg_match('/\[START(.*)?END\]/', $msg, $match)) {
        $parts = explode(' ', $match[1]);
        $request_logs[] = [
          $parts[0], // Date
          str_replace('_', ' ', $parts[1]), // Date Time
          (int)$parts[2], // Hour
          (int)$parts[3], // Minute
          $parts[4], // Application
          $parts[5], // Module
          $parts[6], // Action
          $parts[7], // Status
          (float)$parts[8], // Executive Time
          (int)$parts[9], // Memory Used
          $parts[10], // User IP address
        ];
    } elseif (strpos($msg, '[php') !== false) { // Detect PHP Error log
        // <13>Jan 12 04:52:36 Log-Server [Sun Jan 12 04:52:36.327940 2020] [php7:warn] [pid 29377] [client 14.161.12.25:64472] PHP Warning:  fopen(example.csv): failed to open stream: No such file or directory in /var/www/html/test.php on line 12
        $log = strstr($msg, '[');
        $log_data = getParsedLog($log);

        $php_error_logs[] = [
            date('Y-m-d'),
            $log_data['dateTime'],
            $host,
            $log_data['type'],
            $log_data['file'],
            $log_data['line'],
            $log_data['message'],
            $log_data['ip_address'],
        ];
    } else { // Detect Syslog
        $syslog_logs[] = [
            date('Y-m-d'),
            date('Y-m-d H:i:s'),
            $msg,
            '', // TODO
            $host
        ];
    }
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    $current++;
    if ($current>=$limit) {
        // Start Import server log
        $current = 0;
        // detect request log
        if (count($request_logs)>0) {
            $stat = $db->insert(
                $config['clickhouse']['log_request_table'],
                $request_logs,
                ['lr_date', 'lr_datetime', 'lr_hour', 'lr_minute', 'lr_application', 'lr_module', 'lr_action', 'lr_status', 'lr_exectime', 'lr_memory', 'lr_ip' ]
            );
            echo "\n---=============== Start Log to ".$config['clickhouse']['log_request_table']." with ".count($request_logs)." records ==============-----\n";
            $request_logs = array();
        }
        if (count($php_error_logs)>0) {
            $stat = $db->insert(
                $config['clickhouse']['log_php_error_table'],
                $php_error_logs,
                ['lp_date', 'lp_datetime', 'lp_host', 'lp_type', 'lp_file', 'lp_line', 'lp_message', 'lp_ip' ]
            );
            echo "\n---=============== Start Log to ".$config['clickhouse']['log_php_error_table']." with ".count($php_error_logs)." records ==============-----\n";
            $php_error_logs = array();
        }
        if (count($syslog_logs)>0) {
            $stat = $db->insert(
                $config['clickhouse']['log_syslog_table'],
                $syslog_logs,
                ['ls_date', 'ls_datetime', 'ls_message', 'ls_tag', 'ls_host']
            );
            echo "\n---=============== Start Log to ".$config['clickhouse']['log_syslog_table']." with ".count($syslog_logs)." records ==============-----\n";
            $syslog_logs = array();
        }
    }
};
/*
    queue: Queue from where to get the messages
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: If set to true, automatic acknowledgement mode will be used by this consumer. See https://www.rabbitmq.com/confirms.html for details.
    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
    nowait:
    callback: A PHP Callback
*/
$channel->basic_consume($queue, $consumerTag, false, false, false, false, $callback);

// Loop as long as the channel has callbacks registered
while ($channel->is_consuming()) {
    $channel->wait();
}

function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);
