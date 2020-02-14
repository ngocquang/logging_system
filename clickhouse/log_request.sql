CREATE TABLE default.log_request (
`lr_date` Date,
`lr_datetime` DateTime,
`lr_hour` UInt8,
`lr_minute` UInt8,
`lr_application` String,
`lr_module` String,
`lr_action` String,
`lr_status` String,
`lr_exectime` Float32,
`lr_memory` UInt32,
`lr_ip` String
) ENGINE = MergeTree(lr_date, (lr_datetime, lr_hour, lr_minute, lr_application, lr_module, lr_action, lr_status, lr_exectime, lr_memory, lr_ip), 8192);
