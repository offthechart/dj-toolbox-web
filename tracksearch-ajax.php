<?php

include("authenticate.php");

if (!defined("TBAUTH")) {
  exit();
}

$artist = str_replace("\"","",$_POST['query']);
$title = str_replace("\"","",$_POST['queryn']);

include("../scripts/trackfixer.php");

if (($artist == "") OR ($title == "")) {
  exit(json_encode(array("status"=>"blankinput")));
}

if (strtolower(substr($artist,0,4)) == "the ") {
  $artist = substr($artist,4);
}

$curl = curl_init();
curl_setopt($curl,CURLOPT_URL,"http://www.musicbrainz.org/ws/2/recording/?query=track:%22" . urlencode($title) . "%22%20AND%20artist:%22" . urlencode($artist) . "%22");
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
curl_setopt($curl,CURLOPT_USERAGENT, "OTC DJ Toolbox - Music Reporting Utility");
 
$response = curl_exec($curl);

$decodedresponse = json_decode(json_encode((array) simplexml_load_string($response)),1);

if ($decodedresponse['recording-list']['@attributes']['count'] == 0) {
  exit(json_encode(array("status"=>"noresults")));
}

$recordings = $decodedresponse['recording-list']['recording'];

if (sizeof($recordings) > 0) {
  // All the below need converting into OTC standard capitalisation and durations etc
  $result = array("status"=>"ok","results"=>array());
  if (array_key_exists("title",$recordings)) {
    if ($recordings['length'] != 0) {
      $track_mbid = $recordings['@attributes']['id'];
      $duration = round($recordings['length'] / 1000,0);
      $secs = $duration % 60;
      $mins = ($duration - $secs) / 60;
      $artist = $recordings['artist-credit']['name-credit'];
      if (!array_key_exists("artist",$artist)) {
        $artist = $artist[0];
      }
      $artist_mbid = $artist['artist']['@attributes']['id'];
      $trackinfo = trackfixer(strtolower(html_entity_decode($artist['artist']['name'])),strtolower(html_entity_decode($recordings['title'])));
      $artist = str_replace("]",")",str_replace("[","(",$trackinfo[0]));
      $title = str_replace("]",")",str_replace("[","(",$trackinfo[1]));
      $mix = "";
      if (stristr($title,"(") AND (substr($title,-1) == ")")) {
        if ((strcasecmp(substr($title,-8),"version)") == 0) OR (strcasecmp(substr($title,-4),"mix)") == 0) OR (strcasecmp(substr($title,-5),"edit)") == 0) OR (strcasecmp(substr($title,-6),"(live)") == 0) OR (strcasecmp(substr($title,-10),"(explicit)") == 0) OR (strcasecmp(substr($title,-7),"(clean)") == 0)) {
          $mix = substr(substr($title,strripos($title,"(")+1),0,-1);
          $title = substr($title,0,strripos($title,"("));
        }
      }
      $isrc = "";
      if (array_key_exists("isrc-list",$recordings)) {
        if (sizeof($recordings['isrc-list']['isrc']) == 1) {
          $isrc = $recordings['isrc-list']['isrc']['@attributes']['id'];
        } else {
          for ($j=0;$j<sizeof($recordings['isrc-list']['isrc']);$j++) {
            $isrc = $recordings['isrc-list']['isrc'][$j]['@attributes']['id'];
            if (strtolower(substr($isrc,0,2)) == "gb") {
              break;
            }
          }
        }
      }
      if (($isrc != "") AND (strlen($isrc) == 12)) {
        $isrc = substr($isrc,0,2) . "-" . substr($isrc,2,3) . "-" . substr($isrc,5,2) . "-" . substr($isrc,7,5);
      }
      $result["results"][] = array("artist" => $artist,
                               "title" => $title,
                               "mix" => $mix,
                               "isrc" => $isrc,
                               "label" => "",
                               "mins" => $mins,
                               "secs" => $secs,
			       "artist_mbid" => $artist_mbid,
			       "track_mbid" => $track_mbid);
    }
  } else {
    for ($i = 0;$i<sizeof($recordings);$i++) {
      if (!array_key_exists("length",$recordings[$i])) {
        continue;
      }
      if ($recordings[$i]['length'] != 0) {
        $track_mbid = $recordings[$i]['@attributes']['id'];
        $duration = round($recordings[$i]['length'] / 1000,0);
        $secs = $duration % 60;
        $mins = ($duration - $secs) / 60;
        $artist = $recordings[$i]['artist-credit']['name-credit'];
        if (!array_key_exists("artist",$artist)) {
          $artist = $artist[0];
        }
        $artist_mbid = $artist['artist']['@attributes']['id'];
        $trackinfo = trackfixer(strtolower(html_entity_decode($artist['artist']['name'])),strtolower(html_entity_decode($recordings[$i]['title'])));
        $artist = str_replace("]",")",str_replace("[","(",$trackinfo[0]));
        $title = str_replace("]",")",str_replace("[","(",$trackinfo[1]));
        $mix = "";
        if (stristr($title,"(") AND (substr($title,-1) == ")")) {
          if ((strcasecmp(substr($title,-8),"version)") == 0) OR (strcasecmp(substr($title,-4),"mix)") == 0) OR (strcasecmp(substr($title,-5),"edit)") == 0) OR (strcasecmp(substr($title,-6),"(live)") == 0) OR (strcasecmp(substr($title,-10),"(explicit)") == 0) OR (strcasecmp(substr($title,-7),"(clean)") == 0)) {
            $mix = substr(substr($title,strripos($title,"(")+1),0,-1);
            $title = substr($title,0,strripos($title,"("));
          }
        }
        $isrc = "";
        if (array_key_exists("isrc-list",$recordings[$i])) {
          if (sizeof($recordings[$i]['isrc-list']['isrc']) == 1) {
            $isrc = $recordings[$i]['isrc-list']['isrc']['@attributes']['id'];
          } else {
            for ($j=0;$j<sizeof($recordings[$i]['isrc-list']['isrc']);$j++) {
              $isrc = $recordings[$i]['isrc-list']['isrc'][$j]['@attributes']['id'];
              if (strtolower(substr($isrc,0,2)) == "gb") {
                break;
              }
            }
          }
        }
        if (($isrc != "") AND (strlen($isrc) == 12)) {
          $isrc = substr($isrc,0,2) . "-" . substr($isrc,2,3) . "-" . substr($isrc,5,2) . "-" . substr($isrc,7,5);
        }
        $result["results"][] = array("artist" => $artist,
                               "title" => $title,
                               "mix" => $mix,
                               "isrc" => $isrc,
                               "label" => "",
                               "mins" => $mins,
                               "secs" => $secs,
                               "artist_mbid" => $artist_mbid,
                               "track_mbid" => $track_mbid);
      }
    }
  }
  if (sizeof($result["results"] > 0)) {
    exit(json_encode($result));
  }
}

exit(json_encode(array("status"=>"noresults")));

?>
