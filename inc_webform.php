<?php 
class webform {
function header_debug(){
	header('Content-Type: text');
}

function header($_title=NULL,$_h2=NULL,$_h3=NULL){
	$head=file_get_contents("header.inc", true);
	$title="default";
	$h2="default";
	$h3="";
	if (!is_null($_title))
		$title=$_title;
	if (!is_null($_h2))
		$h2=$_h2;
	if (!is_null($_h3))
		$h3=$_h3;
	$head=str_replace("%title%", $title,$head);
	$head=str_replace("%h2%", $h2,$head);
	$head=str_replace("%h3%", $h3,$head);
	return $head;
}

function footer(){
	$out="\n<hr/>\n</body>\n</html>";
	return $out;
}

function show_DEVindex() {
	echo $this->header("Сетевые устройства","Список сетевых устройств");
	$n=$error=$ok=$fail=0;
	$netdev=new networkDevices();
	$DEVs=$netdev->getDEVlist();
	$loc=$netdev->getDEVlocation();
	echo "<table class='listtab'><tr><th>#</th><th>Статус</th><th>Магазин/Статистика</th><th>IP</th><th>NVR</th><th>Тест</th><th>OK</th><th>DOUB</th><th>BAD</th><th>SPEED</th><th>Код</th><th>время</th><th>Photos</th></tr>";
	foreach($DEVs as $row) {
		$text="<font color=red>ERROR</font>";
		if ($row["httpexitcode"]==200) {
			$text="<font color=green>Ok</font>";
			$ok++;
		} else {
			if ($row["timeout"]<$netdev->getMAX_timewait()) {
               $text="<font color=yellow>Attention</font>";
			   $error++;
			} else {
				$fail++;
			}
		}
		echo "<tr><td>".++$n."</td><td>".$text."</td>
			<td><a title='Статистика' href='?DEVid=".$row["id"]."'>".$row["city"].", ".$row["street"]." (".$row["nick"].")</a></td>
			<td><a title='Открыть адрес в новой вкладке' href='http://".$row["ip"]."' target='_blank'>".$row["ip"]."</a></td>
			<td>".$row["type"]."</td><td><a title='Проверить' href='".$loc."?check=".$row["id"]."'>".date("H:i:s d-m-Y",$row["timestamp"])."</a></td>
			<td>".$row["weekstat"]['good'].'%</td><td>'.$row["weekstat"]['doubtful'].'%</td><td>'.$row["weekstat"]['bad'].'%</td><td>'.$row["weekstat"]['speed']."s</td>
			<td>".$row["httpexitcode"]."</td><td>".$row["timeout"]."</td>
			<td><a title='Открыть адрес в новой вкладке' href='nvrphoto.php?img=".$row["id"].".jpg' target='_blank'>get photo</a></td>
			</tr>\n";
	}
	echo "</table><strong>Всего $n; Доступно: $ok; Недоступно $fail; Ошибки: $error</strong>";
	echo $this->footer();
}

function show_DEVstat($_id) {
	$n=0;
	$error=$ok=$fail=0;
	$id=intval($_id);
	$netdev=new networkDevices();
	$DEVs=$netdev->getDEVstat($id);
	$row=$netdev->getDEVlist()[$id];
	$h3=$row["city"].", ".$row["street"]." (".$row["nick"].")";
	echo $this->header("Статистика устройства","Статистика доступности сетевого устройства",$h3);
	echo "\n<strong>[ <a href='".explode('?', $_SERVER['REQUEST_URI'])[0]."'>Главная</a></strong> ]<br><br>\n";
	if (is_array($DEVs)) {
		echo "<table><tr valign='top'>";
		foreach($DEVs as $DAYs) {
			echo "<td><table align='center' width='200'><tr align='center'><td colspan='3'><b>".date("d-m-Y",$DAYs[1]['start'])."</b> (".$DAYs[1]['speed']."s)</td></tr>\n
			<tr align='center'><td>OK</td><td>DOUB</td><td>BAD</td></tr>\n
			<tr align='center'><td><font color='green'>".$DAYs[1]['good']."%</font></td><td><font color='yellow'>".$DAYs[1]['doubtful']."%</font></td><td><font color='red'>".$DAYs[1]['bad']."%</font></td></tr></table>";
			echo " <table class='listtab'><tr><th>time</th><th>Code</th><th>Out</th></tr>";
			foreach($DAYs[0] as $row)
				echo "<tr><td>".date("H:i:s",$row["timestamp"])."</td><td>".$row["httpexitcode"]."</td><td>".$row["timeout"]."</td></tr>\n";	
			echo "</table></td>";
		}
		echo "</tr></table>";
	} else {
		echo $DEVs;
	}
	echo "\n<br>[ <strong><a href='".explode('?', $_SERVER['REQUEST_URI'])[0]."'>Главная</a></strong> ]\n";
	echo $this->footer();
}

};
?>
