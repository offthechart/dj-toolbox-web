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

/*Further trackfixer issues:

-friday
.friday*/

// Force uid temporarily after auth
/*if ($uid == 1) {
 $uid = 12;
}*/

$usernamequery = mysql_query("SELECT name,pass,mail FROM " . $prefix . "users WHERE uid = '$uid' LIMIT 1");

if (mysql_num_rows($usernamequery) > 0) {
  $username = mysql_result($usernamequery,0,"name");
  $password = mysql_result($usernamequery,0,"pass");
  $email = mysql_result($usernamequery,0,"mail");
} else {
  header("Location: http://www.offthechartradio.co.uk/user?destination=/toolbox");
}

/*if ($uid == 1) {
 $uid = 42;
}*/

$playlistedquery = mysql_query("SELECT " . $prefix . "otc_schedule.schedule_playlisted," . $prefix . "otc_schedule.schedule_start," . $prefix . "otc_schedule.schedule_title," . $prefix . "otc_schedule.schedule_show_id FROM " . $prefix . "otc_schedule, " . $prefix . "otc_shows WHERE " . $prefix . "otc_schedule.schedule_playlisted != 0 AND (" . $prefix . "otc_schedule.schedule_uid = '$uid' OR (" . $prefix . "otc_shows.show_master_uid = '$uid' AND " . $prefix . "otc_shows.show_id = " . $prefix . "otc_schedule.schedule_show_id)) AND " . $prefix . "otc_schedule.schedule_show_type < 2 GROUP BY " . $prefix . "otc_schedule.schedule_id ORDER BY " . $prefix . "otc_schedule.schedule_start DESC LIMIT 10");
$unplaylistedquery = mysql_query("SELECT " . $prefix . "otc_schedule.schedule_playlist_draft," . $prefix . "otc_schedule.schedule_start," . $prefix . "otc_schedule.schedule_title," . $prefix . "otc_schedule.schedule_show_id, " . $prefix . "otc_schedule.schedule_id FROM " . $prefix . "otc_schedule, " . $prefix . "otc_shows WHERE " . $prefix . "otc_schedule.schedule_playlisted = 0 AND (" . $prefix . "otc_schedule.schedule_uid = '$uid' OR (" . $prefix . "otc_shows.show_master_uid = '$uid' AND " . $prefix . "otc_shows.show_id = " . $prefix . "otc_schedule.schedule_show_id)) AND " . $prefix . "otc_schedule.schedule_show_type < 2 GROUP BY " . $prefix . "otc_schedule.schedule_id ORDER BY " . $prefix . "otc_schedule.schedule_start ASC LIMIT 10");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" version="XHTML+RDFa 1.0" dir="ltr"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:dc="http://purl.org/dc/terms/"
  xmlns:foaf="http://xmlns.com/foaf/0.1/"
  xmlns:og="http://ogp.me/ns#"
  xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
  xmlns:sioc="http://rdfs.org/sioc/ns#"
  xmlns:sioct="http://rdfs.org/sioc/types#"
  xmlns:skos="http://www.w3.org/2004/02/skos/core#"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema#">
<head>
  <title>OTC DJ Toolbox</title>
  <link type="text/css" href="css/smoothness/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
  <link type="text/css" href="css/toolbox.css" rel="stylesheet" />
  <noscript><font color="red"><b>You don't seem to have Javascript enabled. Without it you have no hope of viewing this page.</b><br /><br /><br /></font></noscript>
  <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
  <script type="text/javascript" src="js/toolbox.js"></script>
  <script>
    var uid = <?php echo $uid; ?>;
    function reconnectFunction() {
      if (confirm("Are you sure you want to attempt reconnection? You should already have your encoder connected and streaming.")) {
        document.reconnectform.button.disabled = true;
        document.reconnectform.button.value = "Reconnecting...";
        $.get("http://www.offthechartradio.co.uk/scripts/djtools.php?u=<? echo $username; ?>&p=<? echo $password; ?>&o=reconnect", function(data) {
          document.reconnectform.button.disabled = false;
          document.reconnectform.button.value = "Reconnect";
          if (!(data == "Reconnection initiated<br />")) {
            alert("Error: Unable to reconnect - " + data);
          }
        });
      }
    }
	</script>
</head>
<body>
  <div id="header">
    <div id="header-left">
      <h1>OTC DJ Toolbox</h1>
    </div>
    <div id="header-right">
      Logged in as <? echo $username; ?> - <a href="/user/logout">Logout</a>
    </div>
  </div>
  <div id="tabs">
    <ul>
      <li><a href="#tabs-1">Home</a></li>
      <li><a href="#tabs-2">Playlist Logger</a></li>
      <li><a href="#tabs-3">Music Database: Add Track</a></li>
      <!--<li><a href="#tabs-4">Music Database: Edit Artist</a></li>-->
    </ul>
    <div id="tabs-1">
      <div class="row">
        <div id="hh-mini" class="mini-box">
          <div class="mini-header">Live DJ Status</div>
          <div>
            <div id="hh-mini-content">Loading...</div>
            <form onsubmit="reconnectFunction(); return false" name="reconnectform">
              <br /><input type="submit" name="button" value="Reconnect" disabled />
              <label>Disable notifications:</label><input type="checkbox" id="notify" />
            </form>
          </div>
        </div>
        <div id="toth-mini" class="mini-box">
          <div class="mini-header">TOTH Clock</div>
          <div>
            <div id="toth-mini-content">
            Loading...
            </div>
          </div>
        </div>
        <div id="news-mini" class="mini-box">
          <div class="mini-header">Announcements</div>
          <div id="announcement-box">
            Loading...
          </div>
        </div>
      </div>
      <div class="row">
        <div id="contact-mini" class="midi-box">
          <div class="mini-header">Contact Info</div>
          <div id="contact-mini-content">
            <strong>E-mail:</strong> <? echo $email; ?><br />
            <strong>SMS:</strong> OTC + msg to 07766 40 41 42<br />
            <strong>Facebook:</strong> <a href="http://www.facebook.com/offthechartradio" target="_blank">facebook.com/offthechartradio</a><br />
            <strong>Twitter:</strong> <a href="http://twitter.com/offthechart" target="_blank">@offthechart</a><br />
            <strong>Other:</strong> Message Show button via the web player, iPlayer or Android app
          </div>
        </div>
        <div id="links-mini" class="mini-box">
          <div class="mini-header">Useful Links</div>
          <div id="links-mini-content">
            <a href="http://www.offthechartradio.co.uk" target="_blank">Website Home</a><br />
            <a href="http://www.offthechartradio.co.uk/schedule" target="_blank">Schedule</a><br />
            <a href="http://mail.offthechartradio.co.uk" target="_blank">E-mail & SMS Viewer</a><br />
            <a href="http://www.offthechartradio.co.uk/docs/" target="_blank">OTC Documentation</a><br />
            <a href="http://audiologs.offthechartradio.co.uk" target="_blank">Show Audio Logs</a><br />
          </div>
        </div>
      </div>
      <div class="row">
        <div id="playout-mini" class="midi-box">
          <div class="mini-header">Playout System Info</div>
          <div id="playout-mini-content">
            Loading...
          </div>
        </div>
        <div id="playlist-mini" class="mini-box">
          <div class="mini-header">Current Playlist</div>
          <div id="playlist-mini-content">
            No tracks found...
          </div>
        </div>
      </div>
      <div class="row">
        <div id="licensing-mini" class="maxi-box">
          <div class="mini-header">Licensing Info</div>
          <div id="licensing-mini-content">
            Please remember that you are required to follow these rules relating to the frequency of track playout. The above information may help if you are following a playout show, however it is your responsibility to ensure these rules are followed if you are broadcasting after a fronted show.<br /><br />
            In any 3 hour period you must not play: i) more than 3 different tracks from a particular album (compilations excluded), including no more than 2 consecutively, or ii) more than 4 different tracks by a particular artist, including no more than 3 consecutively. <br /><br />
            Although playing the same track more than once in a short period of time is not mentioned, please use some sense here to keep the station sounding professional.
          </div>
        </div>
      </div>
    </div>
    <div id="tabs-2">
      <div id="playlist-maxi">
        <form>
          <select id="playlist-select-edit" tabindex="1" 
          <?
            if (mysql_num_rows($unplaylistedquery) == 0) {
              echo "disabled><option value=\"0\">No Unplaylisted Shows Found</select>";
            } else {
              echo ">";
              echo "<option value=\"0\">Unplaylisted Shows (Edit)</option>";
              while ($row = mysql_fetch_array($unplaylistedquery)) {
                $showtime = strtotime($row['schedule_start']);
                $showtime = date("d/m/Y H:i",$showtime);
                $playlistid = $row['schedule_playlist_draft'];
                if ($playlistid == 0) {
                  $playlistid = "s" . $row['schedule_id'];
                } else {
                  $playlistid = "d" . $playlistid;
                }
                if ($row['schedule_title'] != "") {
                  echo "<option value=\"" . $playlistid . "\">" . $showtime . " - " . $row['schedule_title'] . "</option>";
                } else if ($row['schedule_show_id'] != 0) {
                  $showquery = mysql_query("SELECT show_title FROM " . $prefix . "otc_shows WHERE show_id = '" . $row['schedule_show_id'] . "' LIMIT 1");
                  if ($showquery) {
                    $showname = mysql_result($showquery,0,"show_title");
                  } else {
                    $showname = "Unknown show";
                  }
                  echo "<option value=\"" . $playlistid . "\">" . $showtime . " - " . $showname . "</option>";
                } else {
                  echo "<option value=\"" . $playlistid . "\">" . $showtime . " - " . $username . "</option>";
                }
              }
              echo "</select>";
            }
          ?>
          <select id="playlist-select-view" tabindex="2" 
          <?
            if (mysql_num_rows($playlistedquery) == 0) {
              echo "disabled><option value=\"0\">No Playlisted Shows Found</select>";
            } else {
              echo ">";
              echo "<option value=\"0\">Playlisted Shows (View)</option>";
              while ($row = mysql_fetch_array($playlistedquery)) {
                $showtime = strtotime($row['schedule_start']);
                $showtime = date("d/m/Y H:i",$showtime);
                if ($row['schedule_title'] != "") {
                  echo "<option value=\"" . $row['schedule_playlisted'] . "\">" . $showtime . " - " . $row['schedule_title'] . "</option>";
                } else if ($row['schedule_show_id'] != 0) {
                  $showquery = mysql_query("SELECT show_title FROM " . $prefix . "otc_shows WHERE show_id = '" . $row['schedule_show_id'] . "' LIMIT 1");
                  if ($showquery) {
                    $showname = mysql_result($showquery,0,"show_title");
                  } else {
                    $showname = "Unknown show";
                  }
                  echo "<option value=\"" . $row['schedule_playlisted'] . "\">" . $showtime . " - " . $showname . "</option>";
                } else {
                  echo "<option value=\"" . $row['schedule_playlisted'] . "\">" . $showtime . " - " . $username . "</option>";
                }
              }
              echo "</select>";
            }
          ?>&nbsp;
          <input type="button" value="Submit Playlist" onclick="javascript:submitPlaylist()" id="submit-button" disabled>
          <br /><br />
          <table id="playlist-table-main" class="playlist">
            <tr style="font-weight: bold"><td>Artist</td><td>Title</td><td>Mix / Version</td><td>Duration</td><td>Current Track</td><td colspan="2">Change Order</td><td>Remove</td></tr>
          </table>
          <div id="addtrack" class="ui-widget">
            <label for="artist">Artist: </label><input type="text" name="artist" id="artist" tabindex="4"/>
            <div id="title-container"><label for="title">Title: </label><input type="text" name="title" id="title" tabindex="5"/></div>
            <div id="submit-container"><input type="submit" value="Add To Playlist" onclick="javascript:completeAdd();return false" tabindex="6"></div>
            <br />Can't find your artist or track? Add it <a href="javascript:selectTab(2)">here</a>.
          </div>
          <input type="button" value="Add New Track" onclick="javascript:addTrack();return false" id="addtrack-button" style="margin-top: 15px" tabindex="3">
        </form>
      </div>
    </div>
    <div id="tabs-3">
      Can't find your track in the playlist logger? Search for it here and add it to our database.
      <form>
        <label for="new-artist">Artist: </label><input type="text" name="new-artist" id="new-artist" tabindex="7"/>
        <label for="new-title">Title: </label><input type="text" name="new-title" id="new-title" tabindex="8"/>
        <input type="submit" value="Search" onclick="javascript:newTrackSearch();return false" id="newtrack-search-button" tabindex="9">
      </form>
      <br /><strong>NB:</strong> If you've added a track or artist to our database before then it's still there, so <strong>don't re-add it</strong>! If you can't find it then check your previous playlist for the correct 
spelling.
      <form>
        <table id="lookup-table-main" class="playlist">
          <tr style="font-weight: bold"><td>Artist</td><td>Title</td><td>Mix / Version</td><td>Duration</td><td>Add to Database</td></tr>
        </table>
      </form>
      <div id="lookup-noresults">
        <form>
          <label for="new-mix">Mix / Version: </label><input type="text" name="new-mix" id="new-mix" tabindex="10"/>
          <label for="new-mins">Minutes: </label><input type="text" name="new-mins" id="new-mins" tabindex="11"/>
          <label for="new-secs">Seconds: </label><input type="text" name="new-secs" id="new-secs" tabindex="12"/>
          <label for="new-unsigned">Unsigned Artist?: </label><input type="checkbox" name="new-unsigned" id="new-unsigned" tabindex="13"/>
          <input type="button" value="Add to Database" onclick="javascript:newTrackAdd()" id="newtrack-add-button" tabindex="14">
        </form>
        <br /><strong>IMPORTANT:</strong><br />As you are adding a track manually, please be careful to observe these rules.<br /><br />
        1) When logging an artist name, do not include any featuring artists, simply include the main artist. For example, with the track Madonna Ft. Justin Timerlake - 4 Minutes, log the artist as Madonna. In the case where an artist includes 'vs', generally indicating it as a mash up of two tracks, or a new remix, do include the second artist.<br />
        2) When logging a title, log featuring artists after 'Feat.', and do not include extra information such as 'radio edit' or 'club mix' - that should go in the 'Mix' field.<br />
        3) If, and only if you are not playing the standard radio version of a track, log a mix. Do not include brackets in this, simply Freemasons Remix or eSquire Remix etc, as it is labelled on the track. Do not log a mix as 'radio edit', just miss this out. Please also do not use abbreviations, for example Rmx instead of Remix.<br />
        4) When logging durations, log the entire track's duration.<br />
        5) Be VERY careful with spelling. For every spelling mistake you or anyone else makes, it gives us more manual administration to do as our automated search tools cannot identify the extra details we need to log.<br />
      </div>
    </div>
  </div>
</body>
<script type="text/javascript">
    var serverTime = '<? echo date("F d, Y H:i:s") ?>';
    var clientDate = new Date(serverTime);
    var lastDate = new Date();
</script>
</html>
