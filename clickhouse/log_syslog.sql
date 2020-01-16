CREATE TABLE default.log_syslog (
`ls_date` Date,
 `ls_datetime` DateTime,
 `ls_message` String,
 `ls_tag` String,
 `ls_host` String
) ENGINE = MergeTree(ls_date, (ls_datetime, ls_message, ls_tag, ls_host), 8192);
