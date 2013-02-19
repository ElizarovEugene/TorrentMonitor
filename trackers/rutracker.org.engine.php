<?php
class rutracker
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
		curl_setopt($ch, CURLOPT_URL, "http://login.rutracker.org/forum/login.php");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "login_username={$login}&login_password={$password}&login=%C2%F5%EE%E4");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent($threme, $sess_cookie=0)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://rutracker.org/forum/viewtopic.php?t={$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: rutracker.org\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, "bb_data=".$sess_cookie);
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
		curl_setopt($ch, CURLOPT_URL, "http://dl.rutracker.org/forum/dl.php?t={$threme}");
		curl_setopt($ch, CURLOPT_COOKIE, "bb_data=".$sess_cookie."; bb_dl={$threme}");
		curl_setopt($ch, CURLOPT_REFERER, "http://dl.rutracker.org/forum/dl.php?t={$threme}");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "t={$threme}");
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
		$monthes = array("Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек");
		$month = substr($data, 3, 6);
		$date = preg_replace("/(\d\d)-(\d\d)-(\d\d)/", "$3-$2-$1", str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		$date = date("Y-m-d H:i:s", strtotime($date));
		
		return $date;
	}
	
	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', $data);
		$arr = preg_split("/\s/", $data);
		$date = $arr[0].' '.$arr[1].' 20'.$arr[2].' '.$arr[3];
		
		return $date;		
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
			
			$page = rutracker::login($login, $password);
			
			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match("/profile\.php\?mode=register/", $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match("/bb_data=(.+);/iU", $page, $array))
				{
					rutracker::$sess_cookie = $array[1];
					//запускам процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (rutracker::$warning == NULL)
					{
						rutracker::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (rutracker::$warning == NULL)
				{
					rutracker::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutracker::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (rutracker::$warning == NULL)
			{
				rutracker::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			rutracker::$exucution = FALSE;
		}
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		rutracker::getCookie($tracker);

		if (rutracker::$exucution)
		{
			//получаем страницу для парсинга
			$page = rutracker::getContent($torrent_id, rutracker::$sess_cookie);
			
			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/<span title=\"Когда зарегистрирован\">\[ (.+) \]<\/span>/", $page, $array))
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
							$date = rutracker::dateStringToNum($array[1]);
							$date_str = $array[1];
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
								$torrent = rutracker::getTorrent($torrent_id, rutracker::$sess_cookie);
								$client = ClientAdapterFactory::getStorage('file');
								$client->store($torrent, $id, $tracker, $name, $torrent_id, $timestamp);
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
								//отправляем уведомлении о новом торренте
								$message = $name.' обновлён.';
								Notification::sendNotification('notification', rutracker::dateNumToString($date_str), $tracker, $message);
							}
						}
						else
						{
							//устанавливаем варнинг
							if (rutracker::$warning == NULL)
							{
								rutracker::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							rutracker::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (rutracker::$warning == NULL)
						{
							rutracker::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						rutracker::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (rutracker::$warning == NULL)
					{
						rutracker::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = FALSE;
				}
			}			
			else
			{
				//устанавливаем варнинг
				if (rutracker::$warning == NULL)
				{
					rutracker::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutracker::$exucution = FALSE;
			}
		}
	}
}
?>