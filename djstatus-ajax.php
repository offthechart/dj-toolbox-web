<?
if(!headers_sent()) {
  header("Cache-Control: no-cache, must-revalidate");
  // HTTP/1.1
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Date in the past
}

include("authenticate.php");

if (!defined("TBAUTH")) {
  exit();
}

include('../sites/default/settings.php');
if ($databases['default']['default']['port'] != "") {
  $host = $databases['default']['default']['host'] . ":" . $databases['default']['default']['port'];
} else {
  $host = $databases['default']['default']['host'];
}
$db = mysql_connect($host, $databases['default']['default']['username'], $databases['default']['default']['password']);
if (!$db) {
  exit(json_encode(array("type" => "ERROR","display" => "ERROR")));
}
mysql_select_db($databases['default']['default']['database']);

$prefix = $databases['default']['default']['prefix'];

$serverquery = mysql_query("SELECT server_ip,server_port,server_username,server_password,server_db_name,server_active FROM " . $prefix . "otc_servers");
if (!$serverquery) {
  exit(json_encode(array("type" => "ERROR","display" => "ERROR")));
}
$serverip = "";
while($row = mysql_fetch_array($serverquery)){
    if ($row['server_active'] == 1) {
      $serverip = $row['server_ip'];
      $serverport = $row['server_port'];
      $serveruser = $row['server_username'];
      $serverpass = $row['server_password'];
      $serverdb = $row['server_db_name'];
      break;
    }
}
if ($serverip == "") {
    exit(json_encode(array("type" => "ERROR","display" => "ERROR")));
}
$db = mysql_connect($serverip . ":" . $serverport, $serveruser, $serverpass);

if (!$db) {
  die(json_encode(array("type" => "ERROR","display" => "ERROR")));
}

mysql_select_db($serverdb);

$query = mysql_query("SELECT * FROM otcoutput LIMIT 1");

if (!$query) {
  die(json_encode(array("type" => "ERROR","display" => "ERROR")));
}

$status = mysql_result($query, 0, "status");

if ($status == "onair") {
  exit(json_encode(array("type" => "ONAIR","display" => "<font color=\"red\"><b>ON AIR</b></font><br /><font size=\"2\">Last Updated: " . date("H:i:s") . "</font>")));
} else if ($status == "offair") {
    exit(json_encode(array("type" => "OFFAIR","display" => "<b>OFF AIR</b><br /><font size=\"2\">Last Updated: " . date("H:i:s") . "</font>")));
  } else {
    exit(json_encode(array("type" => "UNKNOWN","display" => "<b>UNKNOWN</b><br /><font size=\"2\">Last Updated: " . date("H:i:s") . "</font>")));
  }

mysql_close($db);

?>
