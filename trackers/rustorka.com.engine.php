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
		$monthes = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
		$month = substr($data, 3, 6);
		$date = preg_replace('/(\d\d)-(\d\d)-(\d\d)/', '$3-$2-$1', str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		$date = date('Y-m-d H:i:s', strtotime($date));

		return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', $data);
		$arr = preg_split('/\s/', $data);
		$date = $arr[0].' '.$arr[1].' 20'.$arr[2].' '.$arr[3];

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
					'referer'        => 'http://rustorka.com/forum/index.php',
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
						Errors::setWarnings($tracker, 'cant_find_cookie');
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
					Errors::setWarnings($tracker, 'cant_get_auth_page');
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
	public static function main($params)
	{
    	extract($params);
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
					'referer'        => 'http://rustorka.com/forum/index.php',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td>Зарегистрирован:<\/td>\r\n{1,2}\s{4}<td>(.*)<\/td>/', $page, $array))
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
                                    
                                    if (Sys::checkTorrentFile($torrent))
                                    {
        								if ($auto_update)
        								{
        								    $name = Sys::parseHeader($tracker, $page);
        								    //обновляем заголовок торрента в базе
                                            Database::setNewName($id, $name);
        								}
    
        								$message = $name.' обновлён.';
        								$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str, $name);
    								
        								//обновляем время регистрации торрента в базе
        								Database::setNewDate($id, $date);
										//сбрасываем варнинг
										Database::clearWarnings($tracker);
										Database::setErrorToThreme($id, 0);
                                    }
                                    else
                                        Errors::setWarnings($tracker, 'torrent_file_fail', $id);
                                }
							}
							Database::setErrorToThreme($id, 0);
						}
						else
						{
							//устанавливаем варнинг
							if (rustorka::$warning == NULL)
							{
								rustorka::$warning = TRUE;
								Errors::setWarnings($tracker, 'cant_find_date', $id);
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
							Errors::setWarnings($tracker, 'cant_find_date', $id);
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
						Errors::setWarnings($tracker, 'cant_find_date', $id);
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
					Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rustorka::$exucution = FALSE;
			}
		}
		rustorka::$warning = NULL;
	}
}
?>