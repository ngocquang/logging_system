<?php

require_once __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Create the server object which listens at 127.0.0.1:9502. Set the server type to SWOOLE_SOCK_UDP
$server = new swoole_server("0.0.0.0", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

// Register callback function for the event `receive data`
$server->on('Packet', function ($server, $data, $clientInfo) {
    global $config;
    //$server->sendto($clientInfo['address'], $clientInfo['port'], "Server : " . $data);

    $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass']);
    $channel = $connection->channel();
    $channel->queue_declare($config['queue_sys_log'], false, true, false, false);

    $msg = new AMQPMessage($data, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    $channel->basic_publish($msg, '', $config['queue_sys_log']);
    // echo " [x] Sent '".$data."'\n";

    $channel->close();
    $connection->close();
});

// Start the server
$server->start();
