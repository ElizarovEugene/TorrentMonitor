<?php
class kiev
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
        		'url'            => 'https://tracker.0day.kiev.ua/',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/<a href=\"userdetails\.php\?id=\d+\">/U', $result))
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
            		'url'            => 'https://tracker.0day.kiev.ua/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password,
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/Имя пользователя или пароль неверны/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kiev::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.*);/U', $page, $array))
				{
					kiev::$sess_cookie = $array[1][1].'; '.$array[1][2].';';
					Database::setCookie($tracker, kiev::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					kiev::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (kiev::$warning == NULL)
					{
						kiev::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_cookie');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kiev::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (kiev::$warning == NULL)
				{
					kiev::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kiev::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kiev::$warning == NULL)
			{
				kiev::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kiev::$exucution = FALSE;
		}

		return kiev::$sess_cookie;
	}

	//основная функция
	public static function main($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (kiev::checkCookie($cookie))
		{
			kiev::$sess_cookie = $cookie;
			//запускам процесс выполнения
			kiev::$exucution = TRUE;
		}
		else
    		kiev::getCookie($tracker);

		if (kiev::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'https://tracker.0day.kiev.ua/details.php?id='.$torrent_id,
            		'cookie'         => kiev::$sess_cookie,
            		'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen(kiev::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/Хэш релиза<\/td><td class=\'row2\' valign=\"top\" align=\"left\">(.*)<\/td><\/tr>/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//сбрасываем варнинг
							Database::clearWarnings($tracker);
							$newHash = $array[1];
							//приводим дату к общему виду
							$date = date('Y-m-d H:i:s');
							//если хэши не совпадают, перекачиваем торрент
							if ($newHash != $hash)
							{
							    //ищем ссылку на скачивание torrent-файла
                                if (preg_match('/download\.php\?id='.$torrent_id.'\&amp\;name=.*\.torrent/', $page, $array))
                                {
                                    $link = str_replace('&amp;', '&', 'https://tracker.0day.kiev.ua/'.$array[0]);
    								//сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                    	array(
                                    		'type'           => 'GET',
                                    		'returntransfer' => 1,
                                    		'url'            => $link,
                                    		'cookie'         => kiev::$sess_cookie,
                                    		'sendHeader'     => array('Host' => 'tracker.0day.kiev.ua', 'Content-length' => strlen(kiev::$sess_cookie)),
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
                                    }
                                    else
                                        Errors::setWarnings($tracker, 'torrent_file_fail', $id);
                                }
                                else
                                {
    								//устанавливаем варнинг
    								if (kiev::$warning == NULL)
    								{
    									kiev::$warning = TRUE;
    									Errors::setWarnings($tracker, 'cant_find_dowload_link', $id);
    								}
    								//останавливаем процесс выполнения, т.к. не может работать без кук
    								kiev::$exucution = FALSE;                                    
                                }
							}
						}
						else
						{
							//устанавливаем варнинг
							if (kiev::$warning == NULL)
							{
								kiev::$warning = TRUE;
								Errors::setWarnings($tracker, 'cant_find_date', $id);
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							kiev::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (kiev::$warning == NULL)
						{
							kiev::$warning = TRUE;
							Errors::setWarnings($tracker, 'cant_find_date', $id);
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						kiev::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (kiev::$warning == NULL)
					{
						kiev::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_date', $id);
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kiev::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (kiev::$warning == NULL)
				{
					kiev::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kiev::$exucution = FALSE;
			}
		}
		kiev::$warning = NULL;
	}
}
?>