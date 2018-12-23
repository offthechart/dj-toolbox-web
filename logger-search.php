<?php
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

/* Data format
[{
  "value" : "test",
  "label" : "test2",
  "desc" : "test3",
  "icon" : ""
}]
*/

if ($_GET['type'] != "id") {
  if ($_GET['term'] == "") {
    exit();
  }
}

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
mysql_set_charset("utf8");

$prefix = $databases['default']['default']['prefix'];

$search = mysql_real_escape_string($_GET['term']);

if ($_GET['type'] == "artist") {
  $artistquery = mysql_query("SELECT artist_id,artist_name,artist_mbid FROM " . $prefix . "otc_artists WHERE artist_name LIKE '%" . $search . "%' ORDER BY LENGTH(artist_name) ASC LIMIT 10");
  if (!$artistquery) {
    exit();
  }
  $output = "[";
  $appender = "";
  $iteration = 0;
  while($row = mysql_fetch_array($artistquery)){
    if ($iteration > 0) {
      $appender .= ",";
    }
    $data = array("value" => utf8_encode($row['artist_name']), "artist" => utf8_encode($row['artist_name']), "mbid" => $row['artist_mbid'], "id" => $row['artist_id']);
    $appender .= json_encode($data);
    $iteration++;
  }
  $output .= $appender . "]";
  print $output;
} else if ($_GET['type'] == "title") {
  $artistid = mysql_real_escape_string($_GET['aid']);
  if ($artistid == "") {
    exit();
  }
  $titlequery = mysql_query("SELECT track_id,track_name,track_mbid,track_duration,track_mix FROM " . $prefix . "otc_tracks WHERE track_name LIKE '%" . $search . "%' AND artist_id = '" . $artistid . "' ORDER BY LENGTH(track_name) ASC LIMIT 10");
  if (!$titlequery) {
    exit();
  }
  $output = "[";
  $appender = "";
  $iteration = 0;
  while($row = mysql_fetch_array($titlequery)){
    if ($iteration > 0) {
      $appender .= ",";
    }
    $mix = "";
    if ($row['track_mix'] != "") {
      $mix = " (" . utf8_encode($row['track_mix']) . ")";
    }
    $duration = $row['track_duration'];
    $secs = ($duration % 60);
    $mins = (($duration - $secs) / 60);
    $duration = " (" . $mins . "m" . $secs . "s" . ")";
    $data = array("value" => utf8_encode($row['track_name']) . $mix . $duration, "track" => utf8_encode($row['track_name']), "mbid" => $row['track_mbid'], "id" => $row['track_id'], "duration" => $row['track_duration'], "mix" => utf8_encode($row['track_mix']));
    $appender .= json_encode($data);
    $iteration++;
  }
  $output .= $appender . "]";
  print $output;
} else if ($_GET['type'] == "id") {
  $trackid = mysql_real_escape_string($_GET['tid']);
  $trackquery = mysql_query("SELECT track_name,track_mbid,track_duration,track_mix,artist_name FROM " . $prefix . "otc_tracks, " . $prefix . "otc_artists WHERE " . $prefix . "otc_tracks.artist_id = " . $prefix . "otc_artists.artist_id AND track_id = '$trackid' LIMIT 1");
  if (!$trackquery) {
    exit();
  }
  while($row = mysql_fetch_array($trackquery)){
    $data = array("artist" => utf8_encode($row['artist_name']), "track" => utf8_encode($row['track_name']), "mbid" => $row['track_mbid'], "duration" => $row['track_duration'], "mix" => utf8_encode($row['track_mix']));
  }
  print json_encode($data);
}
?>
