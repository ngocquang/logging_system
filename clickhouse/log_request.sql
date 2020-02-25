CREATE TABLE default.log_request (
`lr_date` Date,
`lr_datetime` DateTime('Etc/UTC'),
`lr_hour` UInt8,
`lr_minute` UInt8,
`lr_application` String,
`lr_module` String,
`lr_action` String,
`lr_status` String,
`lr_exectime` Float32,
`lr_memory` UInt32,
`lr_ip` String,
`lr_query_count` UInt16,
`lr_query_time` Float32,
`lr_agent` String
) ENGINE = MergeTree PARTITION BY toYYYYMM(`lr_date`) ORDER BY `lr_date`;