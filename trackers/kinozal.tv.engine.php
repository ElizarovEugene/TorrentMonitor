<?php
class kinozal
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;

	//инициализируем класс
	public static function getInstance()
    {
        if ( ! isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
    
	//получаем куки для доступа к сайту
	protected static function login($login, $password)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0");
	    curl_setopt($ch, CURLOPT_HEADER, 1); 
	    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, "http://kinozal.tv/takelogin.php");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, "username={$login}&password={$password}&returnto=");
	    $result = curl_exec($ch);
	    curl_close($ch);
	    
	    $result = iconv("windows-1251", "utf-8", $result);
	    return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://kinozal.tv/details.php?id={$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: kinozal.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем содержимое torrent файла
	public static function getTorrent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$header[] = "Host: kinozal.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_URL, "http://kinozal.tv/download.php/{$threme}/%5Bkinozal.tv%5Did{$threme}.torrent");
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_REFERER, "http://kinozal.tv/details.php?id={$threme}");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	//функция проверки введёного URL`а
	public static function checkRule($data)
	{
		if (preg_match('/\D+/', $data))
			return FALSE;
		else
			return TRUE;
	}
	
	//функция преобразования даты
	private static function dateStringToNum($data)
	{
	    if (strstr($data, 'сегодня') || strstr($data, 'вчера'))
	    {
	        $pieces = explode(' ', $data);
	        if ($pieces[0] == 'вчера')
	            $timestamp = strtotime("-1 day");
	        else         
	            $timestamp = strtotime("now");
	        $date = date('Y-m-d', $timestamp);
	        $time = $pieces[2].':00';
	        $dateTime = $date.' '.$time;
	        return $dateTime;
	    }
	    elseif (preg_match('/\d{2} \D* \d{4} в \d{2}:\d{2}/', $data))
	    {
			$pieces = explode(' ', $data);
			switch ($pieces[1])
			{
			    case "января": $m='01'; break;
			    case "февраля": $m='02'; break;
			    case "марта": $m='03'; break;
			    case "апреля": $m='04'; break;
			    case "мая": $m='05'; break;
			    case "июня": $m='06'; break;
			    case "июля": $m='07'; break;
			    case "августа": $m='08'; break;
			    case "сентября": $m='09'; break;
			    case "октября": $m='10'; break;
			    case "ноября": $m='11'; break;
			    case "декабря": $m='12'; break;
			}
			$date = $pieces[2].'-'.$m.'-'.$pieces[0];
			$time = $pieces[4].':00';
			$dateTime = $date.' '.$time;
			return $dateTime;
	    }
	}
	
	//функция получения кук
	protected static function getCookie($tracker)
	{
		//проверяем заполнены ли учётные данные
		if (Database::checkTrackersCredentialsExist($tracker))
		{
			//получаем учётные данные
			$credentials = Database::getCredentials($tracker);
			$login = iconv("utf-8", "windows-1251", $credentials['login']);
			$password = $credentials['password'];
			
			$page = kinozal::login($login, $password);
			
			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match("/Не верно указан пароль/", $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all("/Set-Cookie: (.+);/iU", $page, $array))
				{
					kinozal::$sess_cookie = $array[1][0].'; '.$array[1][1].';';
					//запускам процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (kinozal::$warning == NULL)
					{
						kinozal::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozal::$warning == NULL)
			{
				kinozal::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozal::$exucution = FALSE;
		}
	}
	
    public static function work($array, $id, $tracker, $name, $torrent_id, $timestamp)
    {
		//проверяем удалось ли получить дату со страницы
		if (isset($array[1]))
		{
			//если дата не равна ничему
			if ( ! empty($array[1]))
			{
				//сбрасываем варнинг
				Database::clearWarnings($tracker);
				//приводим дату к общему виду
				$date = kinozal::dateStringToNum($array[1]);
				$date_str = $array[1];
				//если даты не совпадают, перекачиваем торрент
				if ($date != $timestamp)
				{
					//сохраняем торрент в файл
					$torrent = kinozal::getTorrent($torrent_id, kinozal::$sess_cookie);
					if (preg_match('/<a href=\'\/pay_mode\.php\#tcounter\' class=sbab>/', $torrent))
					{
        				//устанавливаем варнинг
        				if (kinozal::$warning == NULL)
        				{
        					kinozal::$warning = TRUE;
        					Errors::setWarnings($tracker, 'max_torrent');
        				}
        				//останавливаем процесс выполнения
        				kinozal::$exucution = FALSE;
					}
					else
					{
    					$client = ClientAdapterFactory::getStorage('file');
    					$client->store($torrent, $id, $tracker, $name, $id, $timestamp);
    					//обновляем время регистрации торрента в базе
    					Database::setNewDate($id, $date);
    					//отправляем уведомлении о новом торренте
    					$message = $name.' обновлён.';
    					Notification::sendNotification('notification', $date_str, $tracker, $message);
    				}
				}
			}
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozal::$warning == NULL)
			{
				kinozal::$warning = TRUE;
				Errors::setWarnings($tracker, 'not_available');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozal::$exucution = FALSE;
		}
    }
	
	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		kinozal::getCookie($tracker);

		if (kinozal::$exucution)
		{
			//получаем страницу для парсинга
			$page = kinozal::getContent($torrent_id, kinozal::$sess_cookie);

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/<li>Обновлен<span class=\"floatright green n\">(.*)<\/span><\/li>/", $page, $array))
    				kinozal::work($array, $id, $tracker, $name, $torrent_id, $timestamp);
				elseif (preg_match("/<li>Залит<span class=\"floatright green n\">(.*)<\/span><\/li>/", $page, $array))
				    kinozal::work($array, $id, $tracker, $name, $torrent_id, $timestamp);
				else
				{
					//устанавливаем варнинг
					if (kinozal::$warning == NULL)
					{
						kinozal::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без даты
					kinozal::$exucution = FALSE;
				}
			}			
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
	}
}
?>