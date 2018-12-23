<?php
define("TBAUTH","AUTHKEY");

$uid = "";
$username = "Unknown";
$admin = false;

include('../sites/default/settings.php');
if ($databases['default']['default']['port'] != "") {
  $host = $databases['default']['default']['host'] . ":" . $databases['default']['default']['port'];
} else {
  $host = $databases['default']['default']['host'];
}
$db = mysql_connect($host, $databases['default']['default']['username'], $databases['default']['default']['password']);
if (!$db) {
  exit();
}
mysql_select_db($databases['default']['default']['database']);

$prefix = $databases['default']['default']['prefix'];

foreach ($_COOKIE as $key => $value) {
  if ((substr($key,0,4) == "SESS") AND (strlen($key) == 36)) { // TODO Not entirely sure the length will always be the same
    $value = mysql_real_escape_string($value);
    $sessquery = mysql_query("SELECT uid FROM " . $prefix . "sessions WHERE sid = '$value' LIMIT 1");
    if (mysql_num_rows($sessquery) > 0) {
      $uid = mysql_result($sessquery,0,"uid");
      $permquery = mysql_query("SELECT " . $prefix . "users_roles.uid FROM " . $prefix . "role," . $prefix . "users_roles WHERE (" . $prefix . "role.name = 'administrator' OR " . $prefix . "role.name = 'DJs' OR " . $prefix . "role.name = 'Toolbox Only') AND " . $prefix . "role.rid = " . $prefix . "users_roles.rid AND " . $prefix . "users_roles.uid = '$uid'");
      if (!(mysql_num_rows($permquery) > 0)) {
        $uid = 0;
      }
      //TODO Work out if they're an admin - also need to add a lock playlist feature so there can only be one editor at once
      break;
    }
  }
}

if (($uid == "") OR ($uid == 0)) {
  exit(header("Location: http://www.offthechartradio.co.uk/user?destination=/toolbox"));
}
?>
