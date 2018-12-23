    var workingArtistId = "";
    var workingArtistName = "";
    var workingTrackId = "";
    var workingTrackName = "";
    var lastTrackSearch = "";
    var currentPlaylist = "";
    var orderedPlaylist = "";
    var hhWindow;
    var oldHHData = "";
    var enableNotifications = true;
  
    $(function() {
      $("#tabs").tabs();
      
      $.ajaxSetup({
        timeout: 15000
      });
      
      setInterval("updateServerTime()", 250);
      dateUpdate();
      djStatusUpdate();
      playoutStatusUpdate();
      reloadAnnouncements();
      document.reconnectform.button.disabled = false;
      $("#playlist-select-view").prop("selectedIndex",0);
      $("#playlist-select-edit").prop("selectedIndex",0);      
      $("#addtrack-button").css("display","none");

      $("#artist").autocomplete({
        source: "logger-search.php?type=artist",
        minLength: 2,
        autoFocus: true,
        select: function( event, ui ) {
          if (ui.item) {
            $("#title-container").css("display","inline");
            if (event.keyCode != 9) {
              $("#title").focus();
            }
            workingArtistId = ui.item.id;
            workingArtistName = ui.item.artist;
            $("#title").autocomplete({
              source: "logger-search.php?type=title&aid=" + workingArtistId,
              minLength: 2,
              autoFocus: true,
              select: function( event, ui ) {
                if (ui.item) {
                  $("#submit-container").css("display","inline");
                  workingTrackId = ui.item.id;
                  workingTrackName = ui.item.track;
                  workingTrackMix = ui.item.mix;
                  workingTrackDuration = ui.item.duration;
                  workingTrackSecs = workingTrackDuration % 60;
                  workingTrackMins = (workingTrackDuration - workingTrackSecs) / 60;
                }
              }
            });
          }
        }
      });
      
      $("#tabs").tabs({
        select: function(event, ui) {
          if (ui.index == 0) {
            updateHomePlaylist();
          } else if (ui.index == 1) {
            if ($("#playlist-select-edit").prop("selectedIndex") != 0) {
              playlistId = currentPlaylist['playlist'];
              $.get("playlist-ajax.php",{"action":"view","playlist":playlistId},
                function (data) {
                  if (data != "") {
                    var parsedData = JSON.parse(data);
                    currentPlaylist = parsedData;
                    updatePlaylistView(0);
                  }
                }
              );
            }
          }
        },
        show: function(event, ui) {
          if (ui.index == 1) {
            if ($("#addtrack-button").val() == "Add New Track") {
              $("#addtrack-button").focus();
            } else {
              $("#artist").focus();
            }
          } else if (ui.index == 2) {
            $("#new-artist").focus();
          }
        }
      });
      
      $("#artist").keydown(function(event) {
        if (event.keyCode != 13 && event.keyCode != 9 && event.keyCode != 27) {
          $("#title-container").css("display","none");
          $("#submit-container").css("display","none");
          $("#title").val("");
          workingArtistId = "";
          workingArtistName = "";
          workingTrackId = "";
          workingTrackName = "";
          workingTrackMix = "";
          workingTrackDuration = "";
          workingTrackMins = "";
          workingTrackSecs = "";
        }
      });
      
      $("#title").keydown(function(event) {
        if (event.keyCode != 13 && event.keyCode != 9 && event.keyCode != 27) {
          $("#submit-container").css("display","none");
          workingTrackId = "";
          workingTrackName = "";
          workingTrackMix = "";
          workingTrackDuration = "";
          workingTrackMins = "";
          workingTrackSecs = "";
        }
      });
      
      $("#new-artist").keydown(function() {
        $("#lookup-noresults").css("display","none");
        $("#new-mix").val("");
        $("#new-mins").val("");
        $("#new-secs").val("");
        $("#new-unsigned").prop("checked", false);
        $("#lookup-table-main").find("tr:gt(0)").remove();
      });
      
      $("#new-title").keydown(function() {
        $("#lookup-noresults").css("display","none");
        $("#new-mix").val("");
        $("#new-mins").val("");
        $("#new-secs").val("");
        $("#new-unsigned").prop("checked", false);
        $("#lookup-table-main").find("tr:gt(0)").remove();
      });
      
      $("#playlist-select-view").change(function() {
        $('#playlist-select-view').attr("disabled","disabled");
        var editVal = $('#playlist-select-edit').attr("disabled");
        $('#playlist-select-edit').attr("disabled","disabled");
        $('#submit-button').attr("disabled","disabled");

        $("#playlist-select-edit").prop("selectedIndex",0);
        $("#playlist-table-main").find("tr:gt(0)").remove();
        if ($('#playlist-select-view option:selected').val() > 1) {
	  $.get('playlist-ajax.php',{"action":"view","playlist": $('#playlist-select-view option:selected').val()},
            function(data) {
              var parsedData = JSON.parse(data);
              currentPlaylist = parsedData;
              updatePlaylistView(1);
            }
          );
          $("#addtrack-button").css("display","none");
        } else {
          if ($("#playlist-select-view").prop("selectedIndex") != 0) {
            alert("This playlist is in an old format which this viewer cannot read");
            $("#playlist-select-view").prop("selectedIndex",0);
          }
        }
        $('#playlist-select-view').removeAttr("disabled");
        if (editVal != 'disabled') {
          $('#playlist-select-edit').removeAttr("disabled");
        }
      });
      
      $("#playlist-select-edit").change(function() {
        var viewVal = $('#playlist-select-view').attr("disabled");
        $('#playlist-select-view').attr("disabled","disabled");
        $('#playlist-select-edit').attr("disabled","disabled");
        $('#submit-button').attr("disabled","disabled");

        $("#playlist-select-view").prop("selectedIndex",0);
        $("#playlist-table-main").find("tr:gt(0)").remove();
        if ($("#playlist-select-edit").prop("selectedIndex") != 0) {
          //TODO Load playlist here
          var playlistId = 0;
	  if ($('#playlist-select-edit option:selected').val().substring(0,1) == "d") {
            playlistId = $('#playlist-select-edit option:selected').val().substring(1);
            $.get("playlist-ajax.php",{"action":"view","playlist":playlistId},
              function (data) {
                if (data != "") {
                  var parsedData = JSON.parse(data);
                  currentPlaylist = parsedData;
                  updatePlaylistView(0);
                  $("#addtrack-button").css("display","block");
                  $('#submit-button').removeAttr("disabled");
                  $("#addtrack-button").focus();
                }
              }
            );
          } else {
            $.get("playlist-ajax.php",{"action":"create","uid":uid,"schedule":$('#playlist-select-edit option:selected').val().substring(1)},
              function (data) {
                if (data != "") {
                  var parsedData = JSON.parse(data);
                  playlistId = parsedData['playlist'];
                  currentPlaylist = parsedData;
                  updatePlaylistView(0);
                  $("#addtrack-button").css("display","block");
                  $('#submit-button').removeAttr("disabled");
                  $("#addtrack-button").focus();
                }
              }
            );
          }

        } else {
          $("#addtrack-button").css("display","none");
        }

        if (viewVal != 'disabled') {
          $('#playlist-select-view').removeAttr("disabled");
        }
        $('#playlist-select-edit').removeAttr("disabled");
      });
               
      $(".playlist-remove").live('click', function(event) {
        var index = 1;
        for (key in orderedPlaylist) {
          if (index == $(this).closest('tr').index()) {
            key = orderedPlaylist[key][4];
            delete currentPlaylist.tracks[key];
            break;
          }
          index++;
        }
        $(this).closest('tr').remove();
        sendPlaylistUpdate();
      });
      
      $(".playlist-up").live('click', function(event) {
        var row = $(this).closest('tr');
        if (row.prev().prev().length != 0) {
          var index = 1;
          var lastkey = "";
          for (key in orderedPlaylist) {
            if (index == row.index()) {
              key = orderedPlaylist[key][4];
              lastkey = orderedPlaylist[lastkey][4];
              currentPlaylist.tracks[key][2]--;
              currentPlaylist.tracks[lastkey][2]++;
              break;
            }
            lastkey = key;
            index++;
          }
          row.insertBefore(row.prev());
          sendPlaylistUpdate();
        }
      });
      
      $(".playlist-down").live('click', function(event) {
        var row = $(this).closest('tr');
        if (row.next().length != 0) {
          var index = 1;
          var lastkey = "";
          var doNext = 0;
          for (key in orderedPlaylist) {
            if (doNext == 1) {
              key = orderedPlaylist[key][4];
              lastkey = orderedPlaylist[lastkey][4];
              currentPlaylist.tracks[key][2]--;
              currentPlaylist.tracks[lastkey][2]++;
              break;
            }
            if (index == row.index()) {
              doNext = 1;
            }
            lastkey = key;
            index++;
          }
          row.insertAfter(row.next());
          sendPlaylistUpdate();
        }
      });
      
      $(".playlist-display").live('click', function(event) {
        $(this).attr("disabled","disabled");
        var currentTime = new Date();
        var hours = currentTime.getHours();
        var minutes = currentTime.getMinutes();
        if (minutes < 10) {
          minutes = "0" + minutes;
        }
        if (hours < 10) {
          hours = "0" + hours;
        }
        $(this).val("Played at " + hours + ":" + minutes);
        var row = $(this).closest('tr');
        var index = 1;
        for (key in orderedPlaylist) {
          if (index == row.index()) {
            key = orderedPlaylist[key][4];
            currentPlaylist.tracks[key][1] = "now";
            break;
          }
          index++;
        }

        $('#playlist-table-main').find("tr").each(function(){
          $(this).removeClass("playlist-playing");
        });
        row.addClass("playlist-playing");
        row.children('td, th').css('backgroundColor','#99FF99');
        sendPlaylistUpdate();
      });
      
      $("#notify").click(function() {
        if ($(this).prop('checked')) {
	  enableNotifications = false;
	} else {
	  enableNotifications = true;
	}
      });
    });
   
    function addTrack() {
      if ($("#addtrack-button").val() == "Add New Track") {
        $("#addtrack").css("display","block");
        $("#artist").focus();
        $("#addtrack-button").val("Cancel");
      } else {
        $("#addtrack").css("display","none");
        $("#addtrack-button").val("Add New Track");
        $("#artist").val("");
        $("#title").val("");
        $("#title-container").css("display","none");
        $("#submit-container").css("display","none");
      }
    }
    
    function completeAdd() {
      $("#title-container").css("display","none");
      $("#submit-container").css("display","none");
      addTrack();
      
      var track = $('#playlist-table-main tr').length;
      // Submit the playlist details here
      $('#playlist-table-main tr:last').after('<tr><td><a href="http://www.offthechartradio.co.uk/music/artist/' + workingArtistName + '/' + workingTrackName + '" target="_blank">' + workingArtistName + '</a></td><td>' + workingTrackName + '</td><td>' + workingTrackMix + '</td><td>' + workingTrackMins + 'm' + workingTrackSecs + 's</td><td><input type="button" value="Now Playing" class="playlist-display"/></td><td><input type="button" value="Up" class="playlist-up"/></td><td><input type="button" value="Down" class="playlist-down"/></td><td><input type="button" value="Remove" class="playlist-remove"/></td></tr>');

      currentPlaylist.tracks["add"][currentPlaylist.tracks["add"].length] = [workingTrackId,0,$('#playlist-table-main tr:last').index()];

      workingArtistId = "";
      workingArtistName = "";
      workingTrackId = "";
      workingTrackName = "";
      workingTrackMix = "";
      workingTrackDuration = "";
      workingTrackMins = "";
      workingTrackSecs = "";

      sendPlaylistUpdate();
      $("#addtrack-button").focus();
    }

    function replacer(key, value) {
      if (typeof value === 'number' && !isFinite(value)) {
        return String(value);
      }
      return value;
    }

    function sendPlaylistUpdate() {
      $.post("playlist-ajax.php",{"data":JSON.stringify(currentPlaylist, replacer),"playlist":currentPlaylist["playlist"],"action":"update"},
        function (data) {
          currentPlaylist = JSON.parse(data);
	        updatePlaylistView(0);
        }
      ).error(function() { 
          alert("Communications failure. Please reload this playlist to continue.");
          $("#playlist-table-main").attr('disabled', 'disabled');
          $("#addtrack-button").css("display","none");
      });
    }
    
    function djStatusUpdate() {
      $.get("djstatus-ajax.php", function(data) {
        var parsedData = JSON.parse(data);
        if (parsedData['type'] != oldHHData) {
          oldHHData = parsedData['type'];
	  if (enableNotifications) {
            hhWindow = window.open("hh.php?status=" + parsedData['type'],"hhWindow","status=0,toolbar=0,location=0,menubar=0,directories=0,resizable=0,scrollbars=0,height=140,width=300");
            if (!hhWindow) {
              alert("Popup blocker detected. The toolbox will not function properly with a popup blocker. Either disable it, or add offthechartradio.co.uk to the trusted sites, then reload this page.");
            } else {
              hhWindow.moveTo(screen.width-300, screen.height-140);
              hhWindow.focus();
              $(hhWindow).blur(function() {
                hhWindow.focus();
              });
              setTimeout("hhWindow.close()",2500);
            }
	  }
        }
        $("#hh-mini-content").html(parsedData['display']);
      }).error(function() {
        if (oldHHData != "error") {
          oldHHData = "error"; 
          $("#hh-mini-content").html('<font color="black"><b>ERROR</b></font><br />Awaiting connection re-establishment');
	  if (enableNotifications) {
            alert("Connection error. Either you have an internet connection issue or the server is down.");
	  }
        }
      });
      window.setTimeout(djStatusUpdate, 3000);
    }

    function dateUpdate() {
      $.get("date-ajax.php", function(data) {
        clientDate = new Date(data);
        lastDate = new Date();
      });
      window.setTimeout(dateUpdate, 10000);
    }

    function reloadAnnouncements() {
      var unique = (new Date()).getTime();
      $.get("announcement.txt?" + unique, function(data) {
        $("#announcement-box").html(data);
      });
      window.setTimeout(reloadAnnouncements, 10000);
    }
    
    function playoutStatusUpdate() {
      $.get("playoutinfo-ajax.php", function(data) {
        $("#playout-mini-content").html(data);
      });
      window.setTimeout(playoutStatusUpdate, 10000);
    }
    
    function selectTab(index) {
      $("#tabs").tabs('select',index);
    }
    
    function newTrackSearch() {
      $("#lookup-noresults").css("display","none");
      $("#lookup-table-main").find("tr:gt(0)").remove();
      $("#newtrack-search-button").attr("disabled","disabled");
      if ($("#new-artist").val() != "" && $("#new-title").val() != "") {
        var query = $("#new-artist").val();
        var queryn = $("#new-title").val();
        $.post('tracksearch-ajax.php',
          {"query": query,"queryn": queryn},
          function(data) {
            var parsedData = JSON.parse(data);
            if (parsedData['status'] == "noresults") {
              $('#lookup-table-main tr:last').after('<tr><td colspan="5">No results found. Please check for spelling errors or complete the missing details below.</td></tr>');
              $("#lookup-noresults").css("display","block");
              $("#new-mix").focus();
            } else if (parsedData['status'] == "toomany") {
              $('#lookup-table-main tr:last').after('<tr><td colspan="5">Too many results to display. Please refine your search or complete the missing details below.</td></tr>');
              $("#lookup-noresults").css("display","block");
              $("#new-mix").focus();
            } else if (parsedData['status'] == "ok") {
              lastTrackSearch = parsedData['results'];
              for (var track in parsedData['results']) {                
                $('#lookup-table-main tr:last').after('<tr><td>' + parsedData['results'][track]['artist'] + '</td><td>' + parsedData['results'][track]['title'] + '</td><td>' + parsedData['results'][track]['mix'] + '</td><td>' + parsedData['results'][track]['mins'] + "m" + parsedData['results'][track]['secs'] + "s" + '</td><td><input type="button" value="Add to Database" onClick="javascript:addTrackToDB(' + track + ')"/></td></tr>');
              }
              $('#lookup-table-main tr:last').after('<tr><td colspan=\"4\"><td><input type="button" value="None of the above" onClick="javascript:pplResultFail()"/></td></tr>');
            }
            $("#newtrack-search-button").removeAttr("disabled");
          }
        );
      } else {
        alert("You must specify both an artist and a title");
        $("#newtrack-search-button").removeAttr("disabled");
      }
    }
    
    function pplResultFail() {
      $("#lookup-noresults").css("display","block");
      $("#new-mix").focus();
    }
    
    function addTrackToDB(val) {
      if (lastTrackSearch[val]['artist'] == "" || lastTrackSearch[val]['title'] == "") {
        alert("Both artist and title must be completed");
      } else {
        $.post('musicdb-ajax.php',
            {"artist": lastTrackSearch[val]['artist'],"title": lastTrackSearch[val]['title'],"mix": lastTrackSearch[val]['mix'],"mins": lastTrackSearch[val]['mins'],"secs": lastTrackSearch[val]['secs'],"label": lastTrackSearch[val]['label'],"isrc": lastTrackSearch[val]['isrc'],"artist_mbid": lastTrackSearch[val]['artist_mbid'],"track_mbid": lastTrackSearch[val]['track_mbid']},
            function(data) {
              try {
                var parsedData = JSON.parse(data);
                  if (parsedData['status'] == "ok") {
                  alert("Track successfully added to the database. You will now be able to use it in your playlist");
                  $("#new-artist").val("");
                  $("#new-title").val("");
                  $("#new-mix").val("");
                  $("#new-mins").val("");
                  $("#new-secs").val("");
                  $("#new-unsigned").prop("checked", false);
                  $("#new-artist").focus();
                  $("#lookup-noresults").css("display","none");
                  $("#lookup-table-main").find("tr:gt(0)").remove();
                } else if (parsedData['status'] == "exists") {
                  alert("This track already exists in the database. If you think this is a mistake, please contact engineering@offthechartradio.co.uk");
                } else {
                  alert("An error occurred. Please try again");
                }
              } catch (e) {
                alert("An error occurred. Please try again");
              }
            }
        );        
      }
    }
    
    function newTrackAdd() {
      if ($("#new-mins").val() != parseInt($("#new-mins").val()) || $("#new-mins").val() == "") {
        alert("Minutes must be a number");
      } else if ($("#new-secs").val() != parseInt($("#new-secs").val()) || $("#new-secs").val() == "") {
        alert("Seconds must be a number");
      } else if ($("#new-artist").val() == "" || $("#new-title").val() == "") {
        alert("Both artist and title must be completed");
      } else {
        $("#newtrack-add-button").attr("disabled","disabled");
        var unsigned = 0;
        if ($("#new-unsigned").prop("checked")) {
          unsigned = 1;
        }
        $.post('musicdb-ajax.php',
            {"artist": $("#new-artist").val(),"title": $("#new-title").val(),"mix": $("#new-mix").val(),"mins": $("#new-mins").val(),"secs": $("#new-secs").val(),"unsigned": unsigned},
            function(data) {
              try {
                var parsedData = JSON.parse(data);
                if (parsedData['status'] == "ok") {
                  alert("Track successfully added to the database. You will now be able to use it in your playlist");
                  $("#new-artist").val("");
                  $("#new-title").val("");
                  $("#new-mix").val("");
                  $("#new-mins").val("");
                  $("#new-secs").val("");
                  $("#new-unsigned").prop("checked", false);
                  $("#lookup-noresults").css("display","none");
                  $("#lookup-table-main").find("tr:gt(0)").remove();
                } else if (parsedData['status'] == "exists") {
                  alert("Error: This track already exists in the database. If you think this is a mistake, please contact engineering@offthechartradio.co.uk");
                } else if (parsedData['status'] == "signed") {
                  alert("Error: This artist appears to be listed in the database as signed. If you think this is a mistake, please contact engineering@offthechartradio.co.uk");
                } else if (parsedData['status'] == "unsigned") {
                  alert("Error: This artist appears to be listed in the database as unsigned. If you think this is a mistake, please contact engineering@offthechartradio.co.uk");
                } else {
                  alert("An error occurred. Please try again");
                }
              } catch (e) {
                alert("An error occurred. Please try again");
              }
            }
        );
        $("#newtrack-add-button").removeAttr("disabled");
      }
    }

    function updatePlaylistView(readOnly) {
      $("#playlist-table-main").find("tr:gt(0)").remove();
      var parsedData = currentPlaylist;
      var trackArray = new Array();
      var length = 0;
      for (key in parsedData.tracks) {
        if (key != "add") {
          var order = parsedData.tracks[key][2];
          trackArray[order-1] = parsedData.tracks[key];
          trackArray[order-1][4] = key;
          length++;
        }
      }
      orderedPlaylist = trackArray;
      for (var i=0;i<length;i++) {
        var gotArtist = trackArray[i][3][0];
        var gotTitle = trackArray[i][3][1];
        var gotMix = trackArray[i][3][2];
        var gotMbid = trackArray[i][3][4];
        var gotDuration = trackArray[i][3][3];
        var gotSecs = gotDuration % 60;
        var gotMins = (gotDuration - gotSecs) / 60;
        var playTime = trackArray[i][1];
        var playedAt = "";
        if (playTime != 0) {
          var playDate = new Date(playTime*1000);
          var hours = playDate.getHours();
          var minutes = playDate.getMinutes();
          if (minutes < 10) {
            minutes = "0" + minutes;
          }
          if (hours < 10) {
            hours = "0" + hours;
          }
          playedAt = "Played at " + hours + ":" + minutes;
          $('#playlist-table-main').find("tr").each(function(){
            $(this).removeClass("playlist-playing");
            //$(this).children('td, td').css('backgroundColor','#FFFFFF');
          });
        }
        if (readOnly == 1) {
          $('#playlist-table-main tr:last').after('<tr><td><a href="http://www.offthechartradio.co.uk/music/artist/' + gotArtist + '/' + gotTitle + '" target="_blank">' + gotArtist + '</a></td><td>' + gotTitle + '</td><td>' + gotMix + '</td><td>' + gotMins + 'm' + gotSecs + 's</td><td>' + playedAt + '</td><td></td><td></td><td></td></tr>');
        } else {
          if (playTime != 0) {
            $('#playlist-table-main tr:last').after('<tr><td><a href="http://www.offthechartradio.co.uk/music/artist/' + gotArtist + '/' + gotTitle + '" target="_blank">' + gotArtist + '</a></td><td>' + gotTitle + '</td><td>' + gotMix + '</td><td>' + gotMins + 'm' + gotSecs + 's</td><td>' + playedAt + '</td><td><input type="button" value="Up" class="playlist-up"/></td><td><input type="button" value="Down" class="playlist-down"/></td><td><input type="button" value="Remove" class="playlist-remove"/></td></tr>');
            if ((playTime + gotDuration) > (new Date().getTime() / 1000)) {
              $('#playlist-table-main tr:last').addClass("playlist-playing");
              $('#playlist-table-main tr:last').children('td, th').css('backgroundColor','#99FF99');
            }
          } else {
            $('#playlist-table-main tr:last').after('<tr><td><a href="http://www.offthechartradio.co.uk/music/artist/' + gotArtist + '/' + gotTitle + '" target="_blank">' + gotArtist + '</a></td><td>' + gotTitle + '</td><td>' + gotMix + '</td><td>' + gotMins + 'm' + gotSecs + 's</td><td><input type="button" value="Now Playing" class="playlist-display"/></td><td><input type="button" value="Up" class="playlist-up"/></td><td><input type="button" value="Down" class="playlist-down"/></td><td><input type="button" value="Remove" class="playlist-remove"/></td></tr>');
          }
        }
      }
    }

    function submitPlaylist() {
      $.get("playlist-ajax.php",{"action":"submit","playlist":currentPlaylist["playlist"]},
        function(data) {
          $("#playlist-select-edit option[value='" + $('#playlist-select-edit option:selected').val() + "']").remove();  
          $('#submit-button').attr("disabled","disabled");
          $("#playlist-select-view").prop("selectedIndex",0);
          $("#playlist-select-edit").prop("selectedIndex",0);
          $("#playlist-table-main").find("tr:gt(0)").remove();
          $("#addtrack-button").css("display","none");
          alert("Playlist submitted. You will need to reload the toolbox in order to view it");
        }
      ).error(function() {
        alert("Communications failure. Please try submitting the playlist again");
      });
    }

    var days = new Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
    var months = new Array("January","February","March","April","May","June","July","August","September","October","November","December");

    function fixLength(data) {
      if (data.length == 1) {
        data = "0" + data;
      }
      return data;
    }
    
    function getTextualTime() {
      var mins = clientDate.getMinutes();
      var hours = clientDate.getHours();
      var output = "";
      if (mins > 30) {
        hours += 1;
        if (hours == 24) {
          hours = 0;
        }
      }
      if (hours == 0) {
        hours = 12;
      } else if (hours > 12) {
        hours -= 12;
      }
      switch(mins) {
        case 0:
          output = hours.toString() + " o clock";
          break;
        case 15:
          output = "Quarter past " + hours.toString();
          break;
        case 30:
          output = "Half past " + hours.toString();
          break;
        case 45:
          output = "Quarter to " + hours.toString();
          break;
        default:
          if (mins < 30) {
            output = mins.toString() + " minute";
            if (mins != 1) {
              output += "s";
            }
            output += " past " + hours.toString();
          } else if (mins > 30) {
            mins = 60 - mins;
            output = mins.toString() + " minute";
            if (mins != 1) {
              output += "s";
            }
            output += " to " + hours.toString();
          }
      }
      return output;
    }
    
    function getTOTHTime() {
      var secs = clientDate.getSeconds();
      var mins = clientDate.getMinutes();
      var output = "";
      if (secs == 0 && mins == 0) {
        output += "00:00:00";
      } else {
        if (mins >= 59) {
	   output += "<span style=\"color: #FF0000; font-weight: bold\">";
        } else if (mins >= 55) {
          output += "<span style=\"color: #FF0000\">";
        }
        if (secs == 0) {
          output += "00:" + fixLength((60 - mins).toString()) + ":00";
        } else {
          output += "00:" + fixLength((59 - mins).toString()) + ":" + fixLength((60 - secs).toString());
        }
        if (mins >= 55) {
	   output += "</span>";
        }
      }
      return output;
    }
    
    function updateServerTime(){
      var newDate = new Date();
      var diff = ((newDate.getTime()) - (lastDate.getTime()));
      lastDate = newDate;
      clientDate.setTime(clientDate.getTime() + diff);
      //clientDate.setSeconds(clientDate.getSeconds() + 1);
      var dateTimeLine = days[clientDate.getDay()] + ", " + clientDate.getDate() + " " + months[clientDate.getMonth().toString()] + " " + clientDate.getFullYear() + " - " + fixLength(clientDate.getHours().toString()) + ":" + fixLength(clientDate.getMinutes().toString()) + ":" + fixLength(clientDate.getSeconds().toString());
      var untilTOTHLine = getTOTHTime();
      var textualLine = getTextualTime();
      document.getElementById("toth-mini-content").innerHTML = dateTimeLine + "<br /><br />Until TOTH: " + untilTOTHLine + "<br /><br />Textual: " + textualLine;
    }
    
    function updateHomePlaylist() {
      if ($('#playlist-table-main tr').length == 1) {
        $("#playlist-mini-content").html("No tracks found...");
      } else {
        $("#playlist-mini-content").html('<table id="playlist-mini-table" class="playlist"></table>');
        $('#playlist-table-main').find("tr").each(function(){
          $("#playlist-mini-table").append('<tr><td>' + $(this).find("td:eq(0)").html() + "</td><td>" + $(this).find("td:eq(1)").html() + '</td></tr>');
        });
        $("#playlist-mini-table tr:first").css("font-weight","bold");
      }
    }
