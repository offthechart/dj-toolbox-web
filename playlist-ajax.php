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
  exit("ERROR");
}
mysql_select_db($databases['default']['default']['database']);
mysql_set_charset("utf8");

$prefix = $databases['default']['default']['prefix'];

if (isset($_GET['action'])) {
  $action = $_GET['action'];
} else {
  $action = $_POST['action'];
}
if (isset($_POST['data'])) {
  $playlistdata = $_POST['data'];
}

if (($action == "view") OR ($action == "submit")) {
  $playlistid = mysql_real_escape_string($_GET['playlist']);
  if ($playlistid < 2) {
    exit("Invalid playlist ID");
  }
}

if ($action == "update") {
  $jsonresp = json_decode($playlistdata,true);
  $playlistid = mysql_real_escape_string($jsonresp['playlist']);
  if ($playlistid < 2) {
    exit("Invalid playlist ID");
  }
  $viewquery = mysql_query("SELECT * FROM " . $prefix . "otc_playlist_tracks WHERE playlist_id = '$playlistid' AND playlist_automation_insert = '0'");
  while($row = mysql_fetch_array($viewquery)) {
    if (!array_key_exists($row['playlist_unique_id'],$jsonresp['tracks'])) {
      $delquery = mysql_query("DELETE FROM " . $prefix . "otc_playlist_tracks WHERE playlist_unique_id = '" . $row["playlist_unique_id"] . "'");
    } else {
      if ($jsonresp['tracks'][$row['playlist_unique_id']][1] === "now") {
         $jsonresp['tracks'][$row['playlist_unique_id']][1] = time();
      }
      $updatequery = mysql_query("UPDATE " . $prefix . "otc_playlist_tracks SET playlist_track_id = '" . $jsonresp['tracks'][$row['playlist_unique_id']][0] . "', playlist_track_timestamp = '" . $jsonresp['tracks'][$row['playlist_unique_id']][1] . "', playlist_track_order = '" . $jsonresp['tracks'][$row['playlist_unique_id']][2] . "' WHERE playlist_unique_id = '" . $row['playlist_unique_id'] . "'");
    }
  }
  if (array_key_exists("add",$jsonresp['tracks'])) {
    $cyclearray = $jsonresp['tracks']["add"];
    for ($i = 0; $i < sizeof($cyclearray);$i++) {
      if ($cyclearray[$i][1] === "now") {
         $cyclearray[$i][1] = time();
         break;
      }
      $insertquery = mysql_query("INSERT INTO " . $prefix . "otc_playlist_tracks (playlist_id,playlist_track_id,playlist_track_timestamp,playlist_track_order) VALUES ('" . $playlistid . "','" . $cyclearray[$i][0] . "','" . $cyclearray[$i][1] . "','" . $cyclearray[$i][2] . "')");
    }
  }
  $action = "view";
}

if ($action == "create") {
  $schedid = mysql_real_escape_string($_GET['schedule']);
  $gotid = "";
  $selectquery = mysql_query("SELECT schedule_playlist_draft,schedule_playlisted FROM " . $prefix . "otc_schedule WHERE schedule_id = '$schedid' LIMIT 1");
  while($row = mysql_fetch_array($selectquery)) {
    if ($row['schedule_playlisted'] == 0) {
      if ($row['schedule_playlist_draft'] != 0) {
        $gotid = $row['schedule_playlist_draft'];
        $playlistid = $gotid;
        $action = "view";
      }
    } else {
      exit();
    }
  }
  if ($gotid == "") {
    $uid = mysql_real_escape_string($_GET['uid']);
    $insertquery = mysql_query("INSERT INTO " . $prefix . "otc_playlists (uid,playlist_uploaded_timestamp) VALUES ('$uid','0')");
    $playlistid = mysql_insert_id();
    $updatequery = mysql_query("UPDATE " . $prefix . "otc_schedule SET schedule_playlist_draft = '$playlistid' WHERE schedule_id = '$schedid' LIMIT 1");
    print json_encode(array("playlist" => $playlistid,"tracks" => array("add" => array())));
    exit();
  }
}

if ($action == "view") {
  $viewquery = mysql_query("SELECT * FROM " . $prefix . "otc_playlist_tracks LEFT JOIN " . $prefix . "otc_tracks ON " . $prefix . "otc_playlist_tracks.playlist_track_id = " . $prefix . "otc_tracks.track_id LEFT JOIN " . $prefix . "otc_artists ON " . $prefix . "otc_tracks.artist_id = " . $prefix . "otc_artists.artist_id WHERE playlist_id = '$playlistid' ORDER BY playlist_track_order ASC, playlist_track_timestamp ASC");
  $jsonresp = array("playlist" => $playlistid, "tracks" => array("add" => array()));
  $trackorder = 1;
  while($row = mysql_fetch_array($viewquery)) {
    if ($row['playlist_automation_insert'] == 1) {
      $updatequery = mysql_query("UPDATE " . $prefix . "otc_playlist_tracks SET playlist_automation_insert = '0' WHERE playlist_unique_id = '" . $row['playlist_unique_id'] . "'");
    }
    $trackinfo = array($row['artist_name'],$row['track_name'],$row['track_mix'],$row['track_duration'],$row['track_mbid']);
    $jsonresp['tracks'][$row['playlist_unique_id']] = array($row['playlist_track_id'],$row['playlist_track_timestamp'],$trackorder,$trackinfo);
    $trackorder++;
  }
  echo json_encode($jsonresp);
  exit();
}

if ($action == "submit") {
  $selectquery = mysql_query("SELECT schedule_id FROM " . $prefix . "otc_schedule WHERE schedule_playlist_draft = '$playlistid' LIMIT 1");
  while($row = mysql_fetch_array($selectquery)) {
    $updatequery = mysql_query("UPDATE " . $prefix . "otc_schedule SET schedule_playlist_draft = '0', schedule_playlisted = '$playlistid' WHERE schedule_id = '" . $row['schedule_id'] . "' LIMIT 1");
  }
  $plupdatequery = mysql_query("UPDATE " . $prefix . "otc_playlists SET playlist_uploaded_timestamp = '" . time() . "' WHERE playlist_id = '$playlistid' LIMIT 1");
  exit("");
}

mysql_close($db);

?>
