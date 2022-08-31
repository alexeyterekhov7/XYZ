<?php
// version 0.9
include("inc_config.php");

class networkDevices {
private $mysqli;		// для подключения к SQL
private $MAX_timewait;		// время ожидания ответа на запрос
private $MAX_DEV;		// максимально устройств
private $SPEEDround; 		// сколько знаков после запятой в скорости ответа на запрос
private	$STATdays;		// количество дней в статистике
private	$Webagent;		// Агент которым представляемся
private	$PINGdelay;		// Задержка между пингами
private	$DB_IP;
private	$DB_login;
private	$DB_pass;
private	$DB_name;
private	$ErrorFilename;		// файл для ошибок
private	$SQLmutexFilename;	// файл для защиты от масс запросов обновления
private $DEVlocation;
private $DEV_list=array(); 

function __construct($_ACR=null) {
	$cfg=new OZconfig;
	$this->MAX_timewait=$cfg->MAX_timewait;
	$this->MAX_DEV=$cfg->MAX_DEV;
	$this->SPEEDround=$cfg->SPEEDround;
	$this->STATdays=$cfg->STATdays;
	$this->Webagent=$cfg->Webagent;
	$this->PINGdelay=$cfg->PINGdelay;
	$this->DB_IP=$cfg->DB_IP;
	$this->DB_login=$cfg->DB_login;
	$this->DB_pass=$cfg->DB_pass;
	$this->DB_name=$cfg->DB_name;
	$this->DEVlocation=$cfg->DEVlocation;
	$this->ErrorFilename=$cfg->ErrorFilename;
	$this->SQLmutexFilename=$cfg->SQLmutexFilename;

	@$this->mysqli = new mysqli($this->DB_IP, $this->DB_login, $this->DB_pass, $this->DB_name);
	if ($this->mysqli->connect_errno) {
		$this->msgError("neworkDevices.constructor: Не удалось подключиться SQL: ".$this->mysqli->connect_error);
	}
	if (is_null($_ACR))
		$this->DEV_listStatisticsGeneration();
	else {

	}
}

function DEV_load() {
	$this->DEV_list=array();
	if ($result = $this->mysqli->query("SELECT id,type,ip,pass,city,street,nick,UNIX_TIMESTAMP(timestamp) as timestamp, httpexitcode,timeout FROM nvr ORDER BY city,street LIMIT ".(int)$this->MAX_DEV.";")) {
		while ($row = $result->fetch_assoc()) {
			$row['weekstat']="";
			$row['timeout']=round($row['timeout'],$this->SPEEDround);
			$this->DEV_list[$row['id']]=$row;
		}
		$result->close();
		return true;
	}
	else 
		$this->msgError("neworkDevices.DEV_load: Ошибка выполнения SQL запроса: ".$this->mysqli->connect_error);
	return false;
}
// Получает из SQL статистику за период
function Get_SQLGeneralStatInterval($_id,$_Istart,$_Iend) {
	$arr=array("0"=>array(),"1"=>array("good"=>"-","doubtful"=>"-","bad"=>"-","speed"=>"-","start"=>(int)$_Istart));
	$good=$doubtful=$bad=$count=0;
	$speed=0.0;
	if ($result = $this->mysqli->query("SELECT UNIX_TIMESTAMP(timestamp) as timestamp, httpexitcode,timeout FROM nvr_ping WHERE (nvr_id=".(int)$_id.") and (timestamp>='".date("Y-m-d H:i:s",(int)$_Istart)."') and (timestamp<='".date("Y-m-d H:i:s",(int)$_Iend)."') ORDER BY timestamp;")) {

		while ($row = $result->fetch_assoc()) {
			$arr[0][]=$row;
			switch(intval($row["httpexitcode"])) {
				case 200: 
					$good++; 
				break;
				default:
					if ($row["timeout"]>$this->MAX_timewait)	// Превышено время ожидания ответа на запрос
						$bad++;
					else
						$doubtful++; 
				break;				
			}
			$speed+=$row["timeout"];
			$count++;
		}
		if ($count>0) {	// если найдены данные
			$result->close();
			$good=(int)($good/$count*100);
			$doubtful=(int)($doubtful/$count*100);
			$bad=(int)($bad/$count*100);
			$speed=round($speed/$count,$this->SPEEDround);			// средняя скорость ответов на запросы
			$arr[1]=array("good"=>$good,"doubtful"=>$doubtful,"bad"=>$bad,"speed"=>$speed,"start"=>(int)$_Istart);
		}
	} else $this->msgError("neworkDevices.Get_SQLGeneralStatInterval: Ошибка выполнения SQL запроса: ".$this->mysqli->connect_error);
	return $arr;
}

function DayStart($_time) { // 86400 сутки
	return mktime(0,0,0,date("m",$_time),date("d",$_time),date("Y",$_time));
}

// Обновление статистике для устройств
function DEV_listStatisticsGeneration() {
	$Istart=0; 						// за весь период
	$Iend=time();
	$this->DEV_load(); 					// загузить данные о NVR
	foreach($this->DEV_list as $DEVid=>$DEVitem) {
		$this->DEV_list[$DEVid]['weekstat']=$this->Get_SQLGeneralStatInterval($DEVid,$Istart,$Iend)[1];
	}
}

// Для сохранение ошибок
function msgError($msg) {
	file_put_contents($this->ErrorFilename,"Error".date("YmdHis").":".$msg." ".$_SERVER['REQUEST_URI']."\n",FILE_APPEND | LOCK_EX);
	die($msg);
}

// Для сохранение ошибок
function msgWarning($msg) {
	file_put_contents($this->ErrorFilename,"Warning".date("YmdHis").":".$msg." ".$_SERVER['REQUEST_URI']."\n", FILE_APPEND | LOCK_EX);
	return;
}

function pingWeb($url) {
    // Инициализация CURL
    $ch = curl_init();
    // Установка URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // Указываю USERAGENT браузера
    curl_setopt($ch, CURLOPT_USERAGENT, $this->Webagent);
    // Header
    curl_setopt($ch, CURLOPT_NOBODY, false);
    // Редирект
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Возврат строки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Отключение из вывода отладочной информации
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    // Устанавливаю максимальное количество секунд работы
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)($this->MAX_timewait+2));
    // Выполнение
    curl_exec($ch);
    // Получаю код HTTP ответа
    $httpexitcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // Если ответ от сервера > 200 - тогда сайт доступен
    return $httpexitcode;
}

public function checkDEV($_id=0){
	// Защита от слишком чатстых пингов НЕ РАБОТАЕТ!!!
// Нужно, чтоб по одному не улетало слишком много одикаковых запросов.
/*
	if (file_exists($this->SQLmutexFilename)) {
		file_put_contents($this->SQLmutexFilename,"\n",FILE_APPEND | LOCK_EX);
		while ( (time() - filectime($this->SQLmutexFilename)) < rand(0,$this->PINGdelay));
	} else {
		file_put_contents($this->SQLmutexFilename,"\n",FILE_APPEND | LOCK_EX);
		$this->msgError("neworkDevices.checkDEV: нет SQLmutexFilename файла: ");
	}

$result = $mysqli->query("SELECT UNIX_TIMESTAMP(timestamp) as timestamp FROM nvr ORDER BY timestamp DESC LIMIT 1;");
$row = $result->fetch_assoc();
$xtime=$row["timestamp"];
$result->close();
if ($xtime+30>time())
		exit;
*/

	$id=intval($_id);
	if (!$id) { // id не задан
		if ($result = $this->mysqli->query("SELECT id,ip FROM nvr ORDER BY timestamp LIMIT 1;")) {
			$row = $result->fetch_assoc();
			$id=$row["id"];
			$result->close();
		} else $this->msgError("neworkDevices.checkDEV: Ошибка выполнения SQL SELECT ip,id запроса: ".$this->mysqli->connect_error);
	} else {
		$id=intval($_id);
		if ($result = $this->mysqli->query("SELECT ip FROM nvr WHERE id=".$id.";")) {
			$row = $result->fetch_assoc();
			$result->close();
		} else $this->msgError("neworkDevices.checkDEV: Ошибка выполнения SQL SELECT ip запроса: ".$this->mysqli->connect_error);
	}	

	// зачистка старых записей
	$days=$this->DayStart(time()-86400*($this->STATdays-1));
	if ($result = $this->mysqli->query("DELETE FROM nvr_ping WHERE timestamp<'".date("Y-m-d H:i:s",(int)$days)."';"));

	$timestamp = microtime(true);
	$httpexitcode=$this->pingWeb($row["ip"]);
	$timereq=microtime(true) - $timestamp;
	if ($result = $this->mysqli->query("INSERT INTO `nvr_ping` (`id`, `nvr_id`, `timestamp`, `httpexitcode`, `timeout`) VALUES (NULL, '$id', CURRENT_TIMESTAMP, '$httpexitcode', '$timereq');")) {
	} else $this->msgError("neworkDevices.checkDEV: Ошибка выполнения SQL INSERT запроса: ".$this->mysqli->connect_error);
	if ($result = $this->mysqli->query("UPDATE `nvr` SET `timestamp` = NOW(),`httpexitcode`=$httpexitcode,`timeout`=$timereq WHERE `nvr`.`id` = $id;")) {
	} else $this->msgError("neworkDevices.checkDEV: Ошибка выполнения SQL UPDATE запроса: ".$this->mysqli->connect_error);
	@header("Location: ".$this->DEVlocation);
	exit;

}

/*  
****	interfaces ************
*/
public function getMAX_timewait() {
	return $this->MAX_timewait;
} 
public function getDEVlist() {
	return $this->DEV_list;
} 
public function getDEVlocation() {
	return $this->DEVlocation;
} 

public function getDEVstat($id) {
	$DEVstat=array();
	$today=$this->DayStart(time());
	for($i=0;$i<$this->STATdays;$i++) {
		$day=$today-86400*$i;
		$STATs=$this->Get_SQLGeneralStatInterval($id,$day,$day+86399);
		$DEVstat[$i]=$STATs;
	}
	return $DEVstat;
}

}; // END Class networkDevices
?>
