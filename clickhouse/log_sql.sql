CREATE TABLE default.log_sql (
`ls_date` Date,
 `ls_datetime` DateTime,
 `ls_hour` UInt8,
 `ls_minute` UInt8,
 `ls_hosttype` String,
 `ls_host` String,
 `ls_querytype` String,
 `ls_table` String,
 `ls_exectime` Float32,
 `ls_companyid` UInt32,
 `ls_userid` UInt32,
 `ls_traceid` String,
 `ls_tracespanid` String
) ENGINE = MergeTree(ls_date, (ls_datetime, ls_hour, ls_minute, ls_hosttype, ls_host, ls_querytype, ls_table, ls_exectime, ls_companyid, ls_userid, ls_traceid, ls_tracespanid), 8192);
