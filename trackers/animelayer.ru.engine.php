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
				'url'            => 'http://animelayer.ru',
				'cookie'         => $sess_cookie,
				'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen($sess_cookie)),
				'convert'        => array('windows-1251', 'utf-8//IGNORE'),
			)
		);

		if (preg_match('/<a class=\"myname\" href=\"\/userdetails\.php\?id=\d*\">.*<\/a>/U', $result))
			return TRUE;
		else
			return FALSE;
	}
	
	// Функция проверки введёного URL`а
	public static function checkRule($data)
	{
		if (preg_match('/\D+/', $data))
			return FALSE;
		else
		return TRUE;
	}
	
	// Функция преобразования даты
	// private static function dateStringToNum($data) {}
	
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
					'url'            => 'http://animelayer.ru/takelogin.php',
					'postfields'     => 'username='.$login.'&password='.$password.'&returnto=',
					'convert'        => array('windows-1251', 'utf-8//IGNORE'),
				)
			);
			
			if (!empty($page))
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
					animelayer::$sess_cookie = $array[1][0].'; '.$array[1][1].';'.'; '.$array[1][2].';'.'; '.$array[1][3].';'.'; '.$array[1][4].';'.'; '.$array[1][5].';';
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
						Errors::setWarnings($tracker, 'not_available');
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
					Errors::setWarnings($tracker, 'not_available');
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
	
	public static function work($array, $id, $tracker, $name, $torrent_id, $timestamp, $hash)
	{
		// Проверяем удалось ли получить дату со страницы
		if (isset($array[1]))
		{
			if (!empty($array[1]))
			{
				// Сбрасываем варнинг
				Database::clearWarnings($tracker);
				
				$date = $array[1];
				$date_str = $array[1];
				
				// Если даты не совпадают, перекачиваем торрент
				if ($date != $timestamp)
				{
					// Сохраняем торрент в файл
                    $torrent = Sys::getUrlContent(
                    	array(
                    		'type'           => 'POST',
                    		'returntransfer' => 1,
                    		'url'            => 'http://animelayer.ru/download.php?id='.$torrent_id.'&name=[animelayer.ru]_'.$torrent_id.'.torrent',
                    		'cookie'         => animelayer::$sess_cookie,
                    		'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen(animelayer::$sess_cookie)),
                    		'referer'        => 'http://animelayer.ru/details.php?id='.$torrent_id,
                    	)
                    );
                    
					Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash);
					// Обновляем время регистрации торрента в базе
					Database::setNewDate($id, $date);
					// Отправляем уведомлении о новом торренте
					$message = $name.' обновлён.';
					Notification::sendNotification('notification', $date_str, $tracker, $message);
				}
			}
			else
			{
				// Устанавливаем варнинг
				if (animelayer::$warning == NULL)
				{
					animelayer::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
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
				Errors::setWarnings($tracker, 'not_available');
			}
			// Останавливаем процесс выполнения, т.к. не может работать без даты
			animelayer::$exucution = FALSE;
		}
	}
	
	// Основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash)
	{
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
					'url'            => 'http://animelayer.ru/details.php?id='.$torrent_id,
					'cookie'         => animelayer::$sess_cookie,
					'sendHeader'     => array('Host' => 'animelayer.ru', 'Content-length' => strlen(animelayer::$sess_cookie)),
					'convert'        => array('windows-1251', 'utf-8//IGNORE'),
				)
			);
			
			if (!empty($page))
			{
				// Ищем на странице дату регистрации торрента
				if (preg_match('/<td width=\"\" class=\"heading\" valign=\"top\" align=\"right\">Добавлен<\/td><td valign=\"top\" align=\"left\">(.*)<\/td>/', $page, $array))
    				animelayer::work($array, $id, $tracker, $name, $torrent_id, $timestamp, $hash);
				else
				{
					// Устанавливаем варнинг
					if (animelayer::$warning == NULL)
					{
						animelayer::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
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
					Errors::setWarnings($tracker, 'not_available');
				}
				// Останавливаем процесс выполнения, т.к. не может работать без данных
				animelayer::$exucution = FALSE;
			}
		}
	}
}