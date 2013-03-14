<?php
class tapochek
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
	    curl_setopt($ch, CURLOPT_URL, "http://tapochek.net/login.php");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, "login_username={$login}&login_password={$password}&autologin=1&login=%C2%F5%EE%E4");
	    $result = curl_exec($ch);
	    curl_close($ch);
	    
	    $result = iconv("windows-1251", "utf-8", $result);
	    return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent($threme, $sess_cookie)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, "http://tapochek.net/viewtopic.php?t={$threme}");
	    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    $header[] = "Host: tapochek.net\r\n";
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
		curl_setopt($ch, CURLOPT_URL, "http://tapochek.net/download.php?id={$threme}");
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_REFERER, "http://tapochek.net/viewtopic.php?t={$threme}");
		$header[] = "Host: nnm-club.ru\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
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
		$data = explode('-', $pieces[0]);
		switch ($data[1])
		{
		    case 01: $m="Янв"; break;
		    case 02: $m="Фев"; break;
		    case 03: $m="Мар"; break;
		    case 04: $m="Апр"; break;
		    case 05: $m="Мая"; break;
		    case 06: $m="Июн"; break;
		    case 07: $m="Июл"; break;
		    case 08: $m="Авг"; break;
		    case 09: $m="Сен"; break;
		    case 10: $m="Окт"; break;
		    case 11: $m="Ноя"; break;
		    case 12: $m="Дек"; break;
		}
		$date = $data[2].' '.$m.' '.$data[0];
		$time = $pieces[1];
		$dateTime = $date.' '.$time;
		return $dateTime;
	}
	
	//функция получения кук
	private static function getCookie($tracker)
	{
		//проверяем заполнены ли учётные данные
		if (Database::checkTrackersCredentialsExist($tracker))
		{	
			//получаем учётные данные
			$credentials = Database::getCredentials($tracker);
			$login = iconv("utf-8", "windows-1251", $credentials['login']);
			$password = $credentials['password'];
			
			tapochek::$page = tapochek::login($login, $password);
			
			if ( ! empty(tapochek::$page))
			{
				//проверяем не наткнулись ли на капчу
			    if (preg_match('/profile\.php\?mode=confirm/', tapochek::$page))
			    {
					//устанавливаем варнинг
					if (tapochek::$warning == NULL)
					{
						tapochek::$warning = TRUE;
						Errors::setWarnings($tracker, 'captcha');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					tapochek::$exucution = FALSE;
			    }
			    elseif (preg_match_all("/Set-Cookie: (.*);/iU", tapochek::$page, $array))
			    {
			        if (isset($array[1][0]) && isset($array[1][1]))
			        {
			            if ($array[1][0] == 'deleted' || $array[1][1] == 'deleted')
			            {
			                tapochek::$sess_cookie = 'deleted';
			                //останавливаем процесс выполнения, т.к. не может работать без кук
			                tapochek::$exucution = FALSE;
			            }
			            else
			            {
			                tapochek::$sess_cookie = $array[1][0].'; '.$array[1][1].';';
			                //запускам процесс выполнения, т.к. не может работать без кук
			                tapochek::$exucution = TRUE;
			            }
			        }
			        else
			        {
						//устанавливаем варнинг
						if (tapochek::$warning == NULL)
						{
							tapochek::$warning = TRUE;
							Errors::setWarnings($tracker, 'credential_wrong');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						tapochek::$exucution = FALSE;				        
			        }
			    }
			    else
			    {
					//устанавливаем варнинг
					if (tapochek::$warning == NULL)
					{
						tapochek::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					tapochek::$exucution = FALSE;				    
			    }
			}
			else
			{
				//устанавливаем варнинг
				if (tapochek::$warning == NULL)
				{
					tapochek::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				tapochek::$exucution = FALSE;				
			}
		}
		else
		{
			//устанавливаем варнинг
			if (tapochek::$warning == NULL)
			{
				tapochek::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			tapochek::$exucution = FALSE;
		}		 
	}

	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		tapochek::getCookie($tracker);
		if (tapochek::$sess_cookie == 'deleted')
			tapochek::getCookie($tracker);
		
		if (tapochek::$exucution)
		{
			tapochek::$page = tapochek::getContent($torrent_id, tapochek::$sess_cookie);
			if ( ! empty(tapochek::$page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/\[ <span title=\".*\">(.*)<\/span> \]/', tapochek::$page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания		
							if (preg_match("/download\.php\?id=(\d{2,8})/", tapochek::$page, $link))
							{
								//сбрасываем варнинг
								Database::clearWarnings($tracker);
								//приводим дату к общему виду
								$date = $array[1].':00';
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$torrent_id = $link[1];
									$torrent = tapochek::getTorrent($torrent_id, tapochek::$sess_cookie);
									$client = ClientAdapterFactory::getStorage('file');
									$client->store($torrent, $id, $tracker, $name, $id, $timestamp);
									//обновляем время регистрации торрента в базе
									Database::setNewDate($id, $date);
									//отправляем уведомлении о новом торренте
									$message = $name.' обновлён.';
									Notification::sendNotification('notification', tapochek::dateNumToString($array[1]), $tracker, $message);
								}
							}
							else
							{
								//устанавливаем варнинг
								if (tapochek::$warning == NULL)
                    			{
                    				tapochek::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'not_available');
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								tapochek::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (tapochek::$warning == NULL)
                			{
                				tapochek::$warning = TRUE;
                				Errors::setWarnings($tracker, 'not_available');
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							tapochek::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (tapochek::$warning == NULL)
            			{
            				tapochek::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						tapochek::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (tapochek::$warning == NULL)
        			{
        				tapochek::$warning = TRUE;
        				Errors::setWarnings($tracker, 'not_available');
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					tapochek::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (tapochek::$warning == NULL)
    			{
    				tapochek::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				tapochek::$exucution = FALSE;
			}
		}
	}
}	
?>