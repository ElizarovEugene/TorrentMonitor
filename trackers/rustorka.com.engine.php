<?php
class rustorka
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'returntransfer' => 1,
        		'url'            => 'http://rustorka.com/forum/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'rustorka.com', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/Вы зашли как: &nbsp;/', $result))
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
	private static function dateStringToNum($data)
	{
		return $data.':00';
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', $data);
		$arr = preg_split('/\s/', $data);
		$date = $arr[2].' '.Sys::dateNumToString($arr[1]).' '.$arr[0].' '.$arr[3];

		return $date;
	}

	//функция получения кук
	public static function getCookie($tracker)
	{
		//проверяем заполнены ли учётные данные
		if (Database::checkTrackersCredentialsExist($tracker))
		{
			//получаем учётные данные
			$credentials = Database::getCredentials($tracker);
			$login = iconv('utf-8', 'windows-1251', $credentials['login']);
			$password = $credentials['password'];

			//авторизовываемся на трекере
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'http://rustorka.com/forum/login.php',
            		'postfields'     => 'login_username='.$login.'&login_password='.$password.'&login=%C2%F5%EE%E4',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/profile\.php\?mode=register/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rustorka::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match('/bb_data=.+;/U', $page, $array))
				{
					rustorka::$sess_cookie = $array[0];
					Database::setCookie($tracker, rustorka::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					rustorka::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (rustorka::$warning == NULL)
					{
						rustorka::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rustorka::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (rustorka::$warning == NULL)
				{
					rustorka::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rustorka::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (rustorka::$warning == NULL)
			{
				rustorka::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			rustorka::$exucution = FALSE;
		}
		
		return rustorka::$sess_cookie;
	}

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		$cookie = Database::getCookie($tracker);
		if (rustorka::checkCookie($cookie))
		{
			rustorka::$sess_cookie = $cookie;
			//запускам процесс выполнения
			rustorka::$exucution = TRUE;
		}
		else
    		rustorka::getCookie($tracker);

		if (rustorka::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://rustorka.com/forum/viewtopic.php?t='.$torrent_id,
            		'cookie'         => rustorka::$sess_cookie,
            		'sendHeader'     => array('Host' => 'rustorka.com', 'Content-length' => strlen(rustorka::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td>Зарегистрирован:<\/td>\r\n\s{4}<td>(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2})<\/td>/', $page, $array))
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
							$date = rustorka::dateStringToNum($array[1]);
							$date_str = rustorka::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
							    //ищем ссылку на скачивание torrent-файла
                                if (preg_match('/<a href=\"download\.php\?id=(.*)\" class=\"(genmed|seedmed)\">/', $page, $array))
                                {
                                    $link = 'http://rustorka.com/forum/download.php?id='.$array[1];
    								//сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                    	array(
                                    		'type'           => 'POST',
                                    		'returntransfer' => 1,
                                    		'url'            => $link,
                                    		'cookie'         => rustorka::$sess_cookie,
                                    		'sendHeader'     => array('Host' => 'rustorka.com', 'Content-length' => strlen(rustorka::$sess_cookie)),
                                    		'referer'        => 'http://rustorka.com/forum/viewtopic.php?t='.$torrent_id,
                                    	)
                                    );

    								if ($auto_update)
    								{
    								    $name = Sys::getHeader('http://rustorka.com/forum/viewtopic.php?t='.$torrent_id);
    								    //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
    								}

    								$message = $name.' обновлён.';
    								$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
    								//обновляем время регистрации торрента в базе
    								Database::setNewDate($id, $date);
                                }
							}
						}
						else
						{
							//устанавливаем варнинг
							if (rustorka::$warning == NULL)
							{
								rustorka::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							rustorka::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (rustorka::$warning == NULL)
						{
							rustorka::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						rustorka::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (rustorka::$warning == NULL)
					{
						rustorka::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rustorka::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (rustorka::$warning == NULL)
				{
					rustorka::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rustorka::$exucution = FALSE;
			}
		}
	}
}
?>