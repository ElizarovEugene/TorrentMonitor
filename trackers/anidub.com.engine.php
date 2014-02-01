<?php
class anidub
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
        		'url'            => 'http://tr.anidub.com',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

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
        $m = Sys::dateNumToString($dates[1]);
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
			
			//авторизовываемся на трекере
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'http://tr.anidub.com/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password,
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/<td class=\"embedded\">Вы не зарегистрированы в системе\.<\/td>/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = FALSE;
				}
                //проверяем подходят ли учётные данные
				elseif (preg_match_all('/<td class=\"embedded\">Имя пользователя или пароль неверны<\/td>/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					anidub::$exucution = FALSE;
				}				
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.*);/U', $page, $array))
				{
					anidub::$sess_cookie = $array[1][1].'; '.$array[1][2].';';
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
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash)
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
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://tr.anidub.com/details.php?id='.$torrent_id,
            		'cookie'         => anidub::$sess_cookie,
            		'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidub::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );
//print_r($page);
			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td width=\"\" class=\"heading\" valign=\"top\" align=\"right\">Добавлен<\/td><td valign=\"top\" align=\"left\">(.*)<\/td>/', $page, $array))
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
								//сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'http://tr.anidub.com/download.php?id='.$torrent_id.'&name='.$torrent_id_name,
                                		'cookie'         => anidub::$sess_cookie,
                                		'sendHeader'     => array('Host' => 'tr.anidub.com', 'Content-length' => strlen(anidub::$sess_cookie)),
                                		'referer'        => 'http://tr.anidub.com/details.php?id='.$torrent_id,
                                	)
                                );
								Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash);
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