<?php
function getParsedLog($log) {
  // Get the datetime when the error occurred and convert it to berlin timezone
  try {
    $dateArr = [];
    preg_match('~^\[(.*?)\]~', $log, $dateArr);
    $log = str_replace($dateArr[0], '', $log);
    $log = trim($log);

    $t = explode(' ',$dateArr[1]);
    // 'Mon, 30 Jun 2014 11:30:00 +0400'
    $tmp_date = $t[0].', '.$t[2].' '.$t[1].' '.$t[4].' '.$t[3];
    $errorDateTime = strtotime($tmp_date);

    // Trim
    $tempArr = [];
    preg_match('~\[php(.*?)\]~', $log, $tempArr);
    $log = str_replace($tempArr[0], '', $log);
    $log = trim($log);
    $tempArr = [];
    preg_match('~\[pid(.*?)\]~', $log, $tempArr);
    $log = str_replace($tempArr[0], '', $log);
    $log = trim($log);

    // Get Client IP
    $ipArr = [];
    preg_match('~\[client(.*?)\]~', $log, $ipArr);
    $log = str_replace($ipArr[0], '', $log);
    $log = trim($log);
    $ipAddress = $ipArr[1];

  } catch (\Exception $e) {
    $errorDateTime = '';
  }

  // Get the type of the error
  if (false !== strpos($log, 'PHP Warning')) {
    $log = str_replace('PHP Warning:', '', $log);
    $log = trim($log);
    $errorType = 'WARNING';
  } else if (false !== strpos($log, 'PHP Notice')) {
    $log = str_replace('PHP Notice:', '', $log);
    $log = trim($log);
    $errorType = 'NOTICE';
  } else if (false !== strpos($log, 'PHP Fatal error')) {
    $log = str_replace('PHP Fatal error:', '', $log);
    $log = trim($log);
    $errorType = 'FATAL';
  } else if (false !== strpos($log, 'PHP Parse error')) {
    $log = str_replace('PHP Parse error:', '', $log);
    $log = trim($log);
    $errorType = 'SYNTAX';
  } else if (false !== strpos($log, 'PHP Exception')) {
    $log = str_replace('PHP Exception:', '', $log);
    $log = trim($log);
    $errorType = 'EXCEPTION';
  } else {
    $errorType = 'UNKNOWN';
  }

  if (false !== strpos($log, ' on line ')) {
    $errorLine = explode(' on line ', $log);
    $errorLine = trim($errorLine[1]);
    $log = str_replace(' on line ' . $errorLine, '', $log);
  } else {
    $errorLine = substr($log, strrpos($log, ':') + 1);
    $log = str_replace(':' . $errorLine, '', $log);
  }

  $errorFile = explode(' in /', $log);
  $errorFile = '/' . trim($errorFile[1]);
  $log = str_replace(' in ' . $errorFile, '', $log);

  // The message of the error
  $errorMessage = trim($log);

  return [
    'dateTime'   => $errorDateTime,
    'type'       => $errorType,
    'file'       => $errorFile,
    'line'       => (int)$errorLine,
    'message'    => $errorMessage,
    'ip_address' => $ipAddress,
  ];
}
