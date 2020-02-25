CREATE TABLE default.log_sql (
`ls_date` Date,
`ls_datetime` DateTime,
`ls_hour` UInt8,
`ls_minute` UInt8,
`ls_application` String,
`ls_module` String,
`ls_action` String,
`ls_status` String,
`ls_exectime` Float32,
`ls_query_type` String,
`ls_table` String,
`ls_sql` String
) ENGINE = MergeTree PARTITION BY toYYYYMM(`ls_date`) ORDER BY `ls_date`;