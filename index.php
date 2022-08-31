<?php 
include("inc_main.php");
include("inc_webform.php");

if (isset($_GET['check'])) {
	$nobody=new networkDevices("checkID");
	$id=intval($_GET['check']);
	$nobody->checkDEV($id);
}	
if (isset($_GET['DEVid'])) {
	$body=new webform;
	$id=intval($_GET['DEVid']);
	$body->show_DEVstat($id);
} else {
	$body=new webform;
	$body->show_DEVindex();
}
?>
