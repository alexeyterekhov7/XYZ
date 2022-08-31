<?php 
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
	if ($httpcode==200) {
		header("Content-Type: image/jpeg");
		header("Content-Length: " .(string)strlen($response));
		die($response);
	} else {
		echo "ip: $host code:". $httpcode."\n".$response;
	}
//file_put_contents("nvr.jpg",$response); //echo "ip: $host code". $httpcode;
	return $httpcode;
}

include("inc_main.php");
$nobody=new networkDevices();
$arr=$nobody->getDEVlist();
if (isset($_GET['img'])) {
	$id=intval($_GET['img']);
	$value=$arr[$id];
	getNVRphoto($value['pass'],$value['ip']);
}
?>
