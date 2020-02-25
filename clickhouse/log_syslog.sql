CREATE TABLE default.log_syslog (
`ls_date` Date,
`ls_datetime` DateTime,
`ls_message` String,
`ls_tag` String,
`ls_host` String
) ENGINE = MergeTree PARTITION BY toYYYYMM(`ls_date`) ORDER BY `ls_date`;