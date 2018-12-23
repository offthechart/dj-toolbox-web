<?
if(!headers_sent()) {
  header("Cache-Control: no-cache, must-revalidate");
  // HTTP/1.1
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Date in the past
}

//TODO: Potential having to click twice on add to DB issue for new artists

include("authenticate.php");

if (!defined("TBAUTH")) {
  exit();
}

if (!isset($_POST['artist'])) {
  exit();
}

include("../scripts/trackfixer.php");
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

$artist = mysql_real_escape_string(htmlspecialchars_decode($_POST['artist'],ENT_QUOTES));
$title = mysql_real_escape_string(htmlspecialchars_decode($_POST['title'],ENT_QUOTES));
$mix = ucwords(mysql_real_escape_string(str_replace(")","",str_replace("(","",htmlspecialchars_decode($_POST['mix'],ENT_QUOTES)))));
$mins = mysql_real_escape_string($_POST['mins']);
$secs = mysql_real_escape_string($_POST['secs']);
if (isset($_POST['artist_mbid'])) {
  $artist_mbid = mysql_real_escape_string($_POST['artist_mbid']);
} else {
  $artist_mbid = "";
}
if (isset($_POST['track_mbid'])) {
  $track_mbid = mysql_real_escape_string($_POST['track_mbid']);
} else {
  $track_mbid = "";
}
if (isset($_POST['unsigned'])) {
  $unsigned = mysql_real_escape_string($_POST['unsigned']);
} else {
  $unsigned = 0;
}
if (isset($_POST['isrc'])) {
  $isrc = mysql_real_escape_string($_POST['isrc']);
} else {
  $isrc = "";
}
if (isset($_POST['label'])) {
  $label = mysql_real_escape_string(htmlspecialchars_decode($_POST['label'],ENT_QUOTES));
} else {
  $label = "";
}
$duration = $mins * 60 + $secs;

if (!isset($_POST['isrc'])) {
  $trackinfo = trackfixer($artist,$title);
  $artist = $trackinfo[0];
  $title = $trackinfo[1];
}

$a_insertion = "";
if ($artist_mbid != "") {
  $a_insertion = " OR " . $prefix . "otc_artists.artist_mbid = '" . $artist_mbid . "'";
}

$t_insertion = "";
if ($track_mbid != "") {
  $t_insertion = " OR " . $prefix . "otc_tracks.track_mbid = '" . $track_mbid . "'";
}

$checkquery = mysql_query("SELECT " . $prefix . "otc_tracks.track_id FROM " . $prefix . "otc_tracks," . $prefix . "otc_artists WHERE (" . $prefix . "otc_artists.artist_name LIKE '$artist' OR " . $prefix . "otc_artists.artist_ppl_name LIKE '$artist'" . $a_insertion . ") AND ((" . $prefix . "otc_tracks.track_name LIKE '$title' AND " . $prefix . "otc_tracks.track_mix LIKE '$mix')" . $t_insertion . ") AND " . $prefix . "otc_tracks.artist_id = " . $prefix . "otc_artists.artist_id");
if (mysql_num_rows($checkquery) > 0) {
  exit(json_encode(array("status"=>"exists")));
}

$artistquery = mysql_query("SELECT artist_id,artist_unsigned FROM " . $prefix . "otc_artists WHERE artist_name LIKE '$artist' OR artist_ppl_name LIKE '$artist'" . $a_insertion . " LIMIT 1");
if (mysql_num_rows($artistquery) > 0) {
  $artistid = mysql_result($artistquery,0,"artist_id");
  $artistunsigned = mysql_result($artistquery,0,"artist_unsigned");
  if (($artistunsigned == 0) AND ($unsigned == 1)) {
    exit(json_encode(array("status"=>"signed")));
  } else if (($artistunsigned == 1) AND ($unsigned == 0)) {
    exit(json_encode(array("status"=>"unsigned")));
  }
} else {
  //$newartist = mysql_query("INSERT INTO " . $prefix . "otc_artists (artist_name,artist_ppl_name,artist_unsigned) VALUES ('$artist','$artist','$unsigned')");
  // Not sourcing from PPL anymore
  $newartist = mysql_query("INSERT INTO " . $prefix . "otc_artists (artist_name,artist_unsigned,artist_mbid) VALUES ('$artist','$unsigned','$artist_mbid')");
  $artistid = mysql_insert_id();
}

if ($artistid == "") {
  exit();
}

$newtrack = mysql_query("INSERT INTO " . $prefix . "otc_tracks (artist_id,track_name,track_mix,track_duration,track_isrc,track_label,track_mbid) VALUES ($artistid,'$title','$mix','$duration','$isrc','$label','$track_mbid')");

if ($newtrack) {
  exit(json_encode(array("status"=>"ok")));
}


mysql_close($db);

?>
