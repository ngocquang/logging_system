<?php
include(__DIR__ . '/config.php');


// Init ClickHouseDB instant
$db = new ClickHouseDB\Client($config['clickhouse']);
$db->database($config['clickhouse']['database']);


# Drop latest month
$latest_month = date('Ym', strtotime("-2 month"));

# Retention table log_request
$db->write('
    ALTER TABLE default.log_request DROP PARTITION '.$latest_month.'
');
# Retention table log_php_error
$db->write('
    ALTER TABLE default.log_php_error DROP PARTITION '.$latest_month.'
');
# Retention table log_syslog
$db->write('
    ALTER TABLE default.log_syslog DROP PARTITION '.$latest_month.'
');
# Retention table log_sql
$db->write('
    ALTER TABLE default.log_sql DROP PARTITION '.$latest_month.'
');

echo 'done';
