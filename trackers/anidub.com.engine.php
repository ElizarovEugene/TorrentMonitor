<?php
class anidub
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
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, "http://tr.anidub.com/takelogin.php");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username={$login}&password={$password}");
        $result = curl_exec($ch);
        curl_close($ch);
        
        $result = iconv("windows-1251", "utf-8", $result);
        return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://tr.anidub.com/details.php?id={$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: tr.anidub.com\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем содержимое torrent файла
	public static function getTorrent($threme, $name, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://tr.anidub.com/download.php?id={$threme}&name={$name}");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$header[] = "Host: tr.anidub.com";
		$header[] = "Cookie: {$sess_cookie}; PHPSESSID=doshd24p6q83gdd78v12b7ht13; PHPSESSID=69m9e5ggnpqr1c017592krq0s5";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_REFERER, "http://tr.anidub.com/details.php?id={$threme}");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://tr.anidub.com/");		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$header[] = "Host: tr.anidub.com\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		if (preg_match('/<span style=\"color:#000000;\" title=\"Пользователь\">.*<\/span>/', $result))
			return TRUE;
		else
			return FALSE;		  
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
	private static function dateNumToString($data)
	{
        $pieces = explode(' ', $data);
        $dates = explode('-', $pieces[0]);
        switch ($dates[1])
        {
            case '01': $m="янв"; break;
            case '02': $m="фев"; break;
            case '03': $m="мар"; break;
            case '04': $m="апр"; break;
            case '05': $m="мая"; break;
            case '06': $m="июн"; break;
            case '07': $m="июл"; break;
            case '08': $m="авг"; break;
            case '09': $m="сен"; break;
            case '10': $m="окт"; break;
            case '11': $m="ноя"; break;
            case '12': $m="дек"; break;
        }    
        $date = $dates[2].' '.$m.' '.$dates[0];
        $time = substr($pieces[1], 0, -3);
        $dateTime = $date.' '.$time;
        return $dateTime;
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
			
			$page = anidub::login($login, $password);
			
			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match("/<td class=\"embedded\">Вы не зарегистрированы в системе\.<\/td>/", $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all("/Set-Cookie: (.*);/U", $page, $array))
				{
					anidub::$sess_cookie = $array[1][1].'; '.$array[1][2];
					Database::setCookie($tracker, anidub::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (anidub::$warning == NULL)
					{
						anidub::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (anidub::$warning == NULL)
				{
					anidub::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				anidub::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (anidub::$warning == NULL)
			{
				anidub::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			anidub::$exucution = FALSE;
		}
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		$cookie = Database::getCookie($tracker);
		if (anidub::checkCookie($cookie))
		{
			anidub::$sess_cookie = $cookie;
			//запускам процесс выполнения
			anidub::$exucution = TRUE;
		}			
		else
    		anidub::getCookie($tracker);
    		
		if (anidub::$exucution)
		{
			//получаем страницу для парсинга
			$page = anidub::getContent($torrent_id, anidub::$sess_cookie);

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/<td width=\"\" class=\"heading\" valign=\"top\" align=\"right\">Добавлен<\/td><td valign=\"top\" align=\"left\">(.*)<\/td>/", $page, $array))
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
							$date = $array[1];
							$date_str = anidub::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								preg_match('/<a href=\"download\.php\?id=(\d{2,6})&amp;name=(.*)\">/U', $page, $array);
								$torrent_id = $array[1];
								$torrent_id_name = $array[2];

								if (Database::getSetting('download'))
								{
									//сохраняем торрент в файл
									$torrent = anidub::getTorrent($torrent_id, $torrent_id_name, anidub::$sess_cookie);
									$client = ClientAdapterFactory::getStorage('file');
									$client->store($torrent, $id, $tracker, $name, $torrent_id, $timestamp);
								}

								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
								//отправляем уведомлении о новом торренте
								$message = $name.' обновлён.';
								Notification::sendNotification('notification', $date_str, $tracker, $message);
							}
						}
						else
						{
							//устанавливаем варнинг
							if (anidub::$warning == NULL)
							{
								anidub::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							anidub::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (anidub::$warning == NULL)
						{
							anidub::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						anidub::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (anidub::$warning == NULL)
					{
						anidub::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = FALSE;
				}
			}			
			else
			{
				//устанавливаем варнинг
				if (anidub::$warning == NULL)
				{
					anidub::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				anidub::$exucution = FALSE;
			}
		}
	}
}
?>
