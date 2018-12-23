<?php
if ($_GET['status'] == "ONAIR") {
?>
<html>
<title>ON AIR</title>
<div style="text-align: center; width: 100%"><span style="font-size: 36pt; font-family: Arial, sans-serif; color: #FF0000; font-weight: bold">ON AIR</font></div>
</html>
<?
} else if ($_GET['status'] == "OFFAIR") {
?>
<html>
<title>OFF AIR</title>
<div style="text-align: center; width: 100%"><span style="font-size: 36pt; font-family: Arial, sans-serif; color: #000000; font-weight: bold">OFF AIR</font></div>
</html>
<?
} else {
?>
<html>
<title>ERROR</title>
<div style="text-align: center; width: 100%"><span style="font-size: 36pt; font-family: Arial, sans-serif; color: #000000; font-weight: bold">ERROR</font></div>
</html>
<?
}?>
