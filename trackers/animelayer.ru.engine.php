<?php

class animelayer
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	// Проверяем cookie
	public static function checkCookie($sess_cookie)
	{
		$result = Sys::getUrlContent(
			array(
				'type'           => 'POST',
				'returntransfer' => 1,
				'url'            => 'https://animelayer.ru',
				'cookie'         => $sess_cookie,
				'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen($sess_cookie)),
				'convert'        => array('windows-1251', 'utf-8//IGNORE'),
			)
		);

		if (preg_match('/<span class=\"iblock vtop pd10 username\">.*<\/span>/U', $result))
			return TRUE;
		else
			return FALSE;
	}
	
	// Функция проверки введёного URL`а
	public static function checkRule($data)
	{
		if (preg_match('/\w+/', $data))
			return TRUE;
		else
		    return FALSE;
	}
	
	//функция преобразования даты из строки в формат БД
	private static function dateStringToNum($data)
	{
		$date = str_replace('&nbsp;', ' ', $data);
		$pieces = explode(' ', $date);
		if (strlen($pieces[0]) == 1)
			$pieces[0] = '0'.$pieces[0];
		
		$monthes = array('/января/i', '/февраля/i', '/марта/i', '/апреля/i', '/мая/i', '/июня/i', '/июля/i', '/августа/i', '/сентября/i', '/октября/i', '/ноября/i', '/декабря/i');
		$monthes_num = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		$month = preg_replace($monthes, $monthes_num, $pieces[1]);

		preg_match('/\d{1,2}:\d{2}/', $date, $matchs);
		$pieces2 = explode(':', $matchs[0]);      
		if (strlen($pieces2[0]) == 1)
			$hour = '0'.$pieces2[0];
		else
			$hour = $pieces2[0];
			
		if (count($pieces) == 4)
		    return date('Y').'-'.$month.'-'.$pieces[0].' '.$hour.':'.$pieces2[1].':00'; 
		else if (count($pieces) == 5)
		    return $pieces[2].'-'.$month.'-'.$pieces[0].' '.$hour.':'.$pieces2[1].':00';
	}
	
	//функция преобразования даты в строку
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -3);
		$data = preg_split('/\s/', $data);
		$time = $data[1];
		$data = $data[0];
		$data = preg_split('/\-/', $data);

		$month = Sys::dateNumToString($data[1]);
		$date = $data[2].' '.$month.' '.$data[0].' в '.$time;
		
		return $date;
    }
	
	// Функция получения кук
	protected static function getCookie($tracker)
	{
		// Проверяем заполнены ли учётные данные
		if (Database::checkTrackersCredentialsExist($tracker))
		{
			// Получаем учётные данные
			$credentials = Database::getCredentials($tracker);
			$login = iconv('utf-8', 'windows-1251', $credentials['login']);
			$password = $credentials['password'];
			
			// Авторизовываемся на трекере
			$page = Sys::getUrlContent(
				array(
					'type'           => 'POST',
					'header'         => 1,
					'returntransfer' => 1,
					'url'            => 'https://animelayer.ru/auth/login/',
					'postfields'     => 'login='.$login.'&password='.$password,
					'convert'        => array('windows-1251', 'utf-8//IGNORE'),
				)
			);

			if ( ! empty($page))
			{
				// Проверяем подходят ли учётные данные
				if (preg_match('/Имя пользователя или пароль неверны/', $page, $array))
				{
					// Устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					// Останавливаем процесс выполнения, т.к. не может работать без кук
					animelayer::$exucution = FALSE;
				}
				// Если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.+);/iU', $page, $array))
				{
					animelayer::$sess_cookie = '';
					foreach ($array[1] as $val)
					    animelayer::$sess_cookie .= $val.'; ';
					Database::setCookie($tracker, animelayer::$sess_cookie);
					// Запускам процесс выполнения
					animelayer::$exucution = TRUE;
				}
				else
				{
					// Устанавливаем варнинг
					if (animelayer::$warning == NULL)
					{
						animelayer::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_cookie');
					}
					// Останавливаем процесс выполнения, т.к. не может работать без кук
					animelayer::$exucution = FALSE;
				}
			}
			else
			{
				// Устанавливаем варнинг
				if (animelayer::$warning == NULL)
				{
					animelayer::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				// Останавливаем процесс выполнения, т.к. не может работать без кук
				animelayer::$exucution = FALSE;
			}
		}
		else
		{
			// Устанавливаем варнинг
			if (animelayer::$warning == NULL)
			{
				animelayer::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			
			// Останавливаем процесс выполнения, т.к. не может работать без кук
			animelayer::$exucution = FALSE;
		}
	}

	// Основная функция
	public static function main($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (animelayer::checkCookie($cookie))
		{
			animelayer::$sess_cookie = $cookie;
			// Запускам процесс выполнения
			animelayer::$exucution = TRUE;
		}			
		else
			animelayer::getCookie($tracker);
		
		if (animelayer::$exucution)
		{
			// Получаем страницу для парсинга
			$page = Sys::getUrlContent(
				array(
					'type'           => 'POST',
					'header'         => 0,
					'returntransfer' => 1,
					'url'            => 'https://animelayer.ru/torrent/'.$torrent_id.'/',
					'cookie'         => animelayer::$sess_cookie,
					'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen(animelayer::$sess_cookie)),
				)
			);
			if ( ! empty($page))
			{
				// Ищем на странице дату регистрации торрента
				if (preg_match('/<span class=\"(date-updated|date-created)\">(.*)<\/span>/U', $page, $array))
                {
            		// Проверяем удалось ли получить дату со страницы
            		if (isset($array[2]))
            		{
            			if ( ! empty($array[2]))
            			{
            				// Сбрасываем варнинг
            				Database::clearWarnings($tracker);
            				$date = animelayer::dateStringToNum($array[2]);
            				$date_str = animelayer::dateNumToString($date);
            				// Если даты не совпадают, перекачиваем торрент
            				if ($date != $timestamp)
            				{
								var_dump($torrent);
            					// Сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'https://animelayer.ru/torrent/'.$torrent_id.'/download/',
                                		'cookie'         => animelayer::$sess_cookie,
                                		'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen(animelayer::$sess_cookie)),
                                		'referer'        => 'https://animelayer.ru/torrent/'.$torrent_id,
                                	)
                                );
                                
                                if (Sys::checkTorrentFile($torrent))
                                {
                                    if ($auto_update)
    								{
        								$name = Sys::parseHeader($tracker, $page);
    								    //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
                                        //сбрасываем варнинг
        								Database::clearWarnings($tracker);
        								Database::setErrorToThreme($id, 0);
    								}
        								
                                    $message = $name.' обновлён.';
                					$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str, $name);
 								
                					// Обновляем время регистрации торрента в базе
                					Database::setNewDate($id, $date);
									//сбрасываем варнинг
									Database::clearWarnings($tracker);
									Database::setErrorToThreme($id, 0);
                				}
                				else
                                    Errors::setWarnings($tracker, 'torrent_file_fail', $id);
            				}
							Database::setErrorToThreme($id, 0);
            			}
            			else
            			{
            				// Устанавливаем варнинг
            				if (animelayer::$warning == NULL)
            				{
            					animelayer::$warning = TRUE;
            					Errors::setWarnings($tracker, 'cant_find_date', $id);
            				}
            				// Останавливаем процесс выполнения, т.к. не может работать без даты
            				animelayer::$exucution = FALSE;
            			}
            		}
            		else
            		{
            			// Устанавливаем варнинг
            			if (animelayer::$warning == NULL)
            			{
            				animelayer::$warning = TRUE;
            				Errors::setWarnings($tracker, 'cant_find_date', $id);
            			}
            			// Останавливаем процесс выполнения, т.к. не может работать без даты
            			animelayer::$exucution = FALSE;
            		}                    
                }
				else
				{
					// Устанавливаем варнинг
					if (animelayer::$warning == NULL)
					{
						animelayer::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_date', $id);
					}
					// Останавливаем процесс выполнения, т.к. не может работать без даты
					animelayer::$exucution = FALSE;
				}
			}			
			else
			{
				// Устанавливаем варнинг
				if (animelayer::$warning == NULL)
				{
					animelayer::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
				}
				// Останавливаем процесс выполнения, т.к. не может работать без данных
				animelayer::$exucution = FALSE;
			}
		}
		animelayer::$warning = NULL;
	}
}
