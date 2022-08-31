<?php
class OZconfig {
public $MAX_timewait=9.5;			// время ожидания ответа на запрос
public $MAX_DEV=100;				// максимально устройств
public $SPEEDround=3;				// сколько знаков после запятой в скорости ответа на запрос
public $STATdays=7;				// количество дней в статистике
public $PINGdelay=3;				// Задержка между пингами
public $DB_IP="127.0.0.1";
public $DB_login="sec";
public $DB_pass="2HIcDOvtV3DjeV2D!";	
public $DB_name="sec";
public $DEVlocation="/XYZ/index.php";
public $ErrorFilename="/var/www/html/XYZ/log/error.txt";	// файл для ошибок
public $SQLmutexFilename="/var/www/html/XYZ/log/mutex.flg";		// файл для защиты от масс запросов обновления
public $Webagent = "Mozilla/5.0 (Windows NT 10.0; Trident/7.0; rv:11.0) like Gecko";	// Агент которым представляемся
}
?>
