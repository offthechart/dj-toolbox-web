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


// Pingdom checks
$pingdomkey = "pingdom-app-key";

// Init cURL
$curl = curl_init();
// Set target URL
curl_setopt($curl, CURLOPT_URL, "https://api.pingdom.com/api/2.0/checks");
// Set the desired HTTP method (GET is default, see the documentation for each request)
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
// Set user (email) and password
curl_setopt($curl, CURLOPT_USERPWD, "username:password");
// Add a http header containing the application key (see the Authentication section of this document)
curl_setopt($curl, CURLOPT_HTTPHEADER, array("App-Key: " . $pingdomkey));
// Ask cURL to return the result as a string
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
 
// Execute the request and decode the json result into an associative array
$response = json_decode(curl_exec($curl),true);
 
$otcstatus = "Unknown";
// Check for errors returned by the API
if (!isset($response['error'])) {
  // Fetch the list of checks from the response
  $checksList = $response['checks'];
  // Print the names and statuses of all checks in the list
  foreach ($checksList as $check) {
    if ($check['name'] == "OTC Streamer") {
      if ($check['status'] == "up") {
        $otcstatus = "<font color=\"green\">ON AIR</font>";
      } else if ($check['status'] == "down") {
        $otcstatus = "<font color=\"red\">OFF AIR (Confirmed)</font>";
      } else if ($check['status'] == "unconfirmed_down") {
        $otcstatus = "OFF AIR (Unconfirmed)";
      }
    }
  }
}
      
include('../sites/default/settings.php');
if ($databases['default']['default']['port'] != "") {
  $host = $databases['default']['default']['host'] . ":" . $databases['default']['default']['port'];
} else {
  $host = $databases['default']['default']['host'];
}
$db = mysql_connect($host, $databases['default']['default']['username'], $databases['default']['default']['password']);
$prefix = $databases['default']['default']['prefix'];

if ($db) {
  mysql_select_db($databases['default']['default']['database']);
  mysql_set_charset("utf8");
  // Looking for next or time data
  $dbdatetime = date("Y-m-d H:i:s");
  $nowtitle = "";
  $nexttitle = "";
  $iteration = 0;
  $nextstart = 0;
  while ($nexttitle == $nowtitle) {
    if ($iteration == 0) {
      $query = mysql_query("SELECT * FROM " . $prefix . "otc_schedule WHERE schedule_start <= '$dbdatetime' ORDER BY schedule_start DESC LIMIT 1");
    } else {
      $query = mysql_query("SELECT * FROM " . $prefix . "otc_schedule WHERE schedule_start > '$dbdatetime' ORDER BY schedule_start ASC LIMIT 1");
    }

    if (mysql_num_rows($query) > 0) {
      $djid = mysql_result($query, 0, "schedule_uid");
      $showid = mysql_result($query, 0, "schedule_show_id");
      $dbdatetime = mysql_result($query, 0, "schedule_start");
      $showtype = mysql_result($query, 0, "schedule_show_type");
      $replayorigstart = "";
      $currentreplaystart = "";
      if ($showtype == 2) {
        //Replay - get the original
        $originalid = mysql_result($query, 0, "schedule_replay_orig_id");
        if ($originalid != 0) {
          $replayquery = mysql_query("SELECT schedule_start FROM " . $prefix . "otc_schedule WHERE schedule_id = '$originalid' LIMIT 1");
          if (mysql_num_rows($replayquery) > 0) {
            if ($iteration == 0) {
              $currentreplaystart = date("H:i d/m/Y",strtotime(mysql_result($replayquery, 0, "schedule_start")));
            } else {
              $replayorigstart = date("H:i d/m/Y",strtotime(mysql_result($replayquery, 0, "schedule_start")));
            }
          }
        }
      }
      if ($iteration == 0) {
        $initialshowtype = $showtype;
      }
      $title = mysql_result($query, 0, "schedule_title");

      if ($djid != 0) {
        $djquery = mysql_query("SELECT name FROM " . $prefix . "users WHERE uid = '$djid'");
        if (mysql_num_rows($djquery) > 0) {
          if ($title == "") {
            $title = mysql_result($djquery, 0, "name");
          }
          if ($iteration == 0) {
            $initialdj = mysql_result($djquery, 0, "name");
          } else {
            $nextdj = mysql_result($djquery, 0, "name");
          }
        }
      } else if ($showid != 0) {
          $showquery = mysql_query("SELECT show_title,show_master_uid FROM " . $prefix . "otc_shows WHERE show_id = '$showid'");
          if (mysql_num_rows($showquery) > 0) {
            if ($title == "") {
              $title = mysql_result($showquery, 0, "show_title");
            }
            $djid = mysql_result($showquery, 0, "show_master_uid");
            $djquery = mysql_query("SELECT name FROM " . $prefix . "users WHERE uid = '$djid'");
            if (mysql_num_rows($djquery) > 0) {
              if ($iteration == 0) {
                $initialdj = mysql_result($djquery, 0, "name");
              } else {
                $nextdj = mysql_result($djquery, 0, "name");
              }
            }
          }
        }

      if ($iteration == 0) {
        $nowtitle = $title;
        $nexttitle = $title;
      } else {
        $nexttitle = $title;
        $nextstart = $dbdatetime;
      }

    } else {
      exit("false");
    }
    $iteration++;
  }
  
  echo "<b>Station Status</b><br />" . $otcstatus;
  
  echo ' <a href="http://www.offthechartradio.co.uk/status" target="_blank"><img src="http://external.pingdom.com/banners/pingdom_button_80x15.gif" alt="Monitored by Pingdom" width="80" height="15" border="0" /></a><br /><br />';
  
  echo "<b>Show Details</b><br />";
  $showtypes = array("live","pre-recorded","a replay","automated");
  echo "The current show is $nowtitle";
  if (isset($initialdj)) {
    echo " (with $initialdj)";
  }
  if ($currentreplaystart != "") {
    $currentreplaystart = " from " . $currentreplaystart;
  }
  echo ". This show is expected to be " . $showtypes[$initialshowtype] . $currentreplaystart . ".<br />";
  echo "Next on the schedule is $nexttitle";
  if (isset($nextdj)) {
    echo " (with $nextdj)";
  }
  echo " @ " . date("H:i",strtotime($nextstart));
  if ($replayorigstart != "") {
    $replayorigstart = " from " . $replayorigstart;
  }
  echo ". This show is expected to be " . $showtypes[$showtype] . $replayorigstart . ".";
  echo "<br /><br />";
  
  echo "<b>Most Recent Tracks</b><br />";
  //$trackquery = mysql_query("SELECT " . $prefix . "otc_tracks.track_name," . $prefix . "otc_tracks.track_mix," . $prefix . "otc_artists.artist_name," . $prefix . "otc_artists.artist_unsigned," . $prefix . "otc_artists.artist_mbid," . $prefix . "otc_playlist_tracks.playlist_track_timestamp FROM " . $prefix . "otc_playlist_tracks," . $prefix . "otc_tracks," . $prefix . "otc_artists WHERE " . $prefix . "otc_playlist_tracks.playlist_track_id = " . $prefix . "otc_tracks.track_id AND " . $prefix . "otc_tracks.artist_id = " . $prefix . "otc_artists.artist_id ORDER BY " . $prefix . "otc_playlist_tracks.playlist_track_timestamp DESC LIMIT 10");
  $trackquery = mysql_query("SELECT " . $prefix . "otc_tracks.track_name," . $prefix . "otc_tracks.track_mix," . $prefix . "otc_artists.artist_name," . $prefix . "otc_artists.artist_unsigned," . $prefix . "otc_artists.artist_mbid," . $prefix . "otc_playlist_tracks.playlist_track_timestamp FROM " . $prefix . "otc_tracks," . $prefix . "otc_artists,(SELECT " . $prefix . "otc_playlist_tracks.playlist_track_timestamp, " . $prefix . "otc_playlist_tracks.playlist_track_id FROM " . $prefix . "otc_playlist_tracks ORDER BY " . $prefix . "otc_playlist_tracks.playlist_track_timestamp DESC LIMIT 10) AS " . $prefix . "otc_playlist_tracks WHERE " . $prefix . "otc_playlist_tracks.playlist_track_id = " . $prefix . "otc_tracks.track_id AND " . $prefix . "otc_tracks.artist_id = " . $prefix . "otc_artists.artist_id");
        while($row = mysql_fetch_array($trackquery))
            {
            if ($row['track_mix'] != "") {
              $row['track_mix'] = " (" . $row['track_mix'] . ")";
            }
            if ($row['artist_unsigned'] == 1) {
              echo $row['track_name'] . $row['track_mix'] . " by " . $row['artist_name'];
            } else {
              echo $row['track_name'] . $row['track_mix'] . " by <a href=\"http://www.offthechartradio.co.uk/music/artist/" . $row['artist_name'] . "/" . $row['track_name'] . "/" . $row['artist_mbid'] . "\" target=\"_blank\">" . $row['artist_name'] . "</a>";
            }
            if ($row['playlist_track_timestamp'] != "") {
              echo " @ " . date("H:i (d/m/y)",$row['playlist_track_timestamp']);
            }
            echo "<br />";
        }
  echo "<br />";
} else {
  echo "Database error. Cannot retrieve show data.<br /><br />";
}


?>
