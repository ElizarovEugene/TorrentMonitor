<?php
class Sys
{
	public static function checkInternet()
	{
		$page = file_get_contents('http://ya.ru');
		if (preg_match('/<title>Яндекс<\/title>/', $page))
			return TRUE;
		else
			return FALSE;
	}

	public static function checkConfig()
	{
		$configFile = dirname(__FILE__).'/../config.php';
		if (file_exists($configFile))
		{
			include_once($configFile);
			$confArray = Config::$confArray;
			foreach ($confArray as $key => $val)
			{
				if (empty($val))
					return FALSE;
			}
			return TRUE;
		}
		else
			return FALSE;
	}

	public static function checkCurl()
	{
		if (in_array("curl", get_loaded_extensions()))
			return TRUE;
		else
			return FALSE;
	}
	
	public static function checkWriteToTorrentPath($path)
	{
		if (substr($path, -1) == '/')
			$path = $path;
		else
			$path = $path.'/';
			
		//грабли!? почему ! даёт true?
		if (!file_put_contents($path.'file', ''))
		{
			unlink($path.'file');
			return TRUE;
		}
		else
			return FALSE;
	}
	
	public static function version()
	{
		return '0.7';
	}

	public static function checkUpdate()
	{
		$xml = simplexml_load_file('http://korphome.ru/torrent_monitor/version.xml');
		if (Sys::version() < $xml->current_version)
			return TRUE;
		else
			return FALSE;
	}
	
	//Получаем заголовок страницы
	public static function getHeader($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "{$url}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		
		preg_match("/<title>(.+?.)<\/title>/is", $result, $array);
		if ( ! empty($array[1]))
		{
			$name = $array[1];
			$url = parse_url($url);
			if ($url["host"] == 'nnm-club.ru')
				$name = substr($name, 0, -23);
			if ($url["host"] == 'rutracker.org')
				$name = substr($name, 0, -34);
		}
		else
			$name = "Неизвестный";
		return $name;
	}
	
	//преобразуем месяц из числового в текстовый
	public static function dateNumToString($data)
	{
		switch ($data)
		{
			case 1: $m="Янв"; break;
			case 2: $m="Фев"; break;
			case 3: $m="Мар"; break;
			case 4: $m="Апр"; break;
			case 5: $m="Мая"; break;
			case 6: $m="Июн"; break;
			case 7: $m="Июл"; break;
			case 8: $m="Авг"; break;
			case 9: $m="Сен"; break;
			case 10: $m="Окт"; break;
			case 11: $m="Ноя"; break;
			case 12: $m="Дек"; break;
		}
		return $m;
	}
	
	public static function lastStart()
	{
		$date = date("d-m-Y H:i:s");
		file_put_contents(dirname(__FILE__).'/../laststart.txt', $date);
	}
}
?>