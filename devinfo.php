<?php
// Alpha version

function getNVRphoto($pass,$host) {
    $agent = "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0";
    $url = "http://".$host."/cgi-bin/snapshot.cgi?channel=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "admin:$pass");
    $response=curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	echo "code: ".$httpcode." ".$response;
}

function getInfo($pass,$host) {
    $agent = "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0";
    $url = "http://".$host."/cgi-bin/magicBox.cgi?action=getSystemInfo";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "admin:$pass");
    $response=curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	echo "code: ".$httpcode." ".$response;
}

include("inc_main.php");
$nobody=new networkDevices();
$arr=$nobody->getDEVlist();
$i=0;
foreach($arr as $value) {
	echo ++$i.": ".$value['nick'].", ip: ".$value['ip']." ".$value['type']." ";
	if (($value['httpexitcode']==200)&&($value['type']=="Dahua")) {
		getInfo($value['pass'],$value['ip']);
	} else 
		echo "httpexitcode: ".$value['httpexitcode'];
	echo "<br>\n";
}

?>
