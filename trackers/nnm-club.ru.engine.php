<?php
class nnmclub
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	protected static $page;	
	
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
		curl_setopt($ch, CURLOPT_URL, "http://nnm-club.ru/forum/login.php");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "username={$login}&password={$password}&login=%C2%F5%EE%E4");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://www.nnm-club.ru/forum/viewtopic.php?t={$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: nnm-club.ru\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем содержимое torrent файла
	public static function getTorrent($threme, $sess_cookie, $where)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://www.nnm-club.ru/forum/download.php?id={$threme}");
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.nnm-club.ru/forum/viewtopic.php?t={$threme}");
		$header[] = "Host: nnm-club.ru\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		file_put_contents($where, $result);
	}
	
	public static function checkRule($data)
	{
		if (preg_match('/\D+/', $data))
			return FALSE;
		else
			return TRUE;
	}
	
	//функция получения заголовка страницы
	public static function getTitle($torrent_id)
	{
		//получаем страницу для парсинга
		$page = nnmclub::getContent($torrent_id);
		
		//ищем на странице дату регистрации торрента
		if (preg_match("/<title>(.+)<\/title>/", $page, $array))
			$name = str_replace(" :: NNM-Club.ru", "", $array[1]);
		else
			$name = "Неизвестно";
		return $name;
	}
	
	private static function dateStringToNum($data)
	{
		$monthes = array("Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек");
		$month = mb_substr($data, 3, 6);
		echo $month.'<br>';
		$date = preg_replace("/(\d\d)\s(\d\d)\s(\d\d\d\d)/", "$3-$2-$1",str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		$date = date("Y-m-d H:i:s", strtotime($date));
	
		return $date;
	}
	
	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$date = substr($data, 0, -3);
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
			
			nnmclub::$page = nnmclub::login($login, $password);
			
			if ( ! empty(nnmclub::$page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match("/login\.php\?redirect=/", nnmclub::$page, $array))
				{
					//устанавливаем варнинг
					if (nnmclub::$warning == NULL)
					{
						nnmclub::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					nnmclub::$exucution = FALSE;
				}
				else
				{
					//если подходят - получаем куки
					if (preg_match_all("/Set-Cookie: (.*);/iU", nnmclub::$page, $array))
					{
						nnmclub::$sess_cookie = $array[1][0]."; ".$array[1][3];
						//запускам процесс выполнения, т.к. не может работать без кук
						nnmclub::$exucution = TRUE;
					}
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (nnmclub::$warning == NULL)
				{
					nnmclub::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				nnmclub::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (nnmclub::$warning == NULL)
			{
				nnmclub::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			nnmclub::$exucution = FALSE;
		}
	}
	
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		nnmclub::getCookie($tracker);	
		
		if (nnmclub::$exucution)
		{
			nnmclub::$page = nnmclub::getContent($torrent_id, nnmclub::$sess_cookie);
			
			if ( ! empty(nnmclub::$page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/<td class=\"genmed\">&nbsp;(\d{2}\s\D{6}\s\d{4}\s\d{2}:\d{2}:\d{2})<\/td>/", nnmclub::$page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания		
							if (preg_match("/download\.php\?id=(\d{6,8})/", nnmclub::$page, $link))
							{
								//сбрасываем варнинг
								Database::clearWarnings($tracker);
								//приводим дату к общему виду
								$date = nnmclub::dateStringToNum($array[1]);
								$date_str = $array[1];
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$torrent_id = $link[1];
									$path = Database::getSetting('path');
									$file = $path.'[nnm-club.ru]_'.$torrent_id.'.torrent';
									nnmclub::getTorrent($torrent_id, nnmclub::$sess_cookie, $file);
									//обновляем время регистрации торрента в базе
									Database::setNewDate($id, $date);
									//отправляем уведомлении о новом торренте
									$message = $name.' обновлён.';
									Notification::sendNotification('notification', nnmclub::dateNumToString($date_str), $tracker, $message);
								}
							}
							else
							{
								//устанавливаем варнинг
								if (nnmclub::$warning == NULL)
                    			{
                    				nnmclub::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'not_available');
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								nnmclub::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (nnmclub::$warning == NULL)
                			{
                				nnmclub::$warning = TRUE;
                				Errors::setWarnings($tracker, 'not_available');
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							nnmclub::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (nnmclub::$warning == NULL)
            			{
            				nnmclub::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						nnmclub::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (nnmclub::$warning == NULL)
        			{
        				nnmclub::$warning = TRUE;
        				Errors::setWarnings($tracker, 'not_available');
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					nnmclub::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (nnmclub::$warning == NULL)
    			{
    				nnmclub::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				nnmclub::$exucution = FALSE;
			}
		}
	}
}
?>