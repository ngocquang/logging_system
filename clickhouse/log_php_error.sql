CREATE TABLE default.log_php_error (
`lp_date` Date,
`lp_datetime` DateTime,
`lp_host` String,
`lp_type` String,
`lp_file` String,
`lp_line` UInt32,
`lp_message` String,
`lp_ip` String
) ENGINE = MergeTree PARTITION BY toYYYYMM(`lp_date`) ORDER BY `lp_date`;