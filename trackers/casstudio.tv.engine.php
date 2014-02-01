<?php
class casstudio
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
        		'url'            => 'http://casstudio.tv/',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'casstudio.tv', 'Content-length' => strlen($sess_cookie)),
        	)
        );

		if (preg_match('/<a href=\"logout\.php\">Выход [ (.*) ]<\/a>/', $result))
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
    	$arr = preg_split('/\s/', $data);
    	
    	$dates = preg_split('/-/', $arr[0]);
    	$date = $dates[2].' '.Sys::dateNumToString($dates[1]).' '.$dates[0];
    	
    	$time = substr($arr[1], 0, -3);
    	
    	return $date.' в '.$time;
    }

	//функция получения кук
	protected static function getCookie($tracker)
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
            		'url'            => 'http://casstudio.tv/takelogin.php',
            		'postfields'     => 'login=submit&username='.$login.'&password='.$password.'&x=0&y=0',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/<b>Ошибка входа<\/b><br \/>Имя пользователя или пароль неверны/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					casstudio::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
				{
					casstudio::$sess_cookie = implode('; ', $array[1]);
					Database::setCookie($tracker, casstudio::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					casstudio::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (casstudio::$warning == NULL)
					{
						casstudio::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					casstudio::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (casstudio::$warning == NULL)
				{
					casstudio::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				casstudio::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (casstudio::$warning == NULL)
			{
				casstudio::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			casstudio::$exucution = FALSE;
		}
	}

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash)
	{
		$cookie = Database::getCookie($tracker);
		if (casstudio::checkCookie($cookie))
		{
			casstudio::$sess_cookie = $cookie;
			//запускам процесс выполнения
			casstudio::$exucution = TRUE;
		}
		else
    		casstudio::getCookie($tracker);

		if (casstudio::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://casstudio.tv/details.php?id='.$torrent_id,
            		'cookie'         => casstudio::$sess_cookie,
            		'sendHeader'     => array('Host' => 'casstudio.tv', 'Content-length' => strlen(casstudio::$sess_cookie)),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<li><strong>Дата:<\/strong> (.*)<\/li>/', $page, $array))
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
							$date_str = casstudio::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
							    if (preg_match('/download\.php\?id='.$torrent_id.'&amp;name=(.*)\.torrent/', $page, $link))
							    {
    								//сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'http://casstudio.tv/download.php?id='.$torrent_id.'&name='.$link[1].'.torrent',
                                		'cookie'         => casstudio::$sess_cookie.'; bb_dl='.$torrent_id,
                                		'sendHeader'     => array('Host' => 'casstudio.tv', 'Content-length' => strlen(casstudio::$sess_cookie.'; bb_dl='.$torrent_id)),
                                		'referer'        => 'http://casstudio.tv/details.php?id='.$torrent_id,
                                    	)
                                    );
    								Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash);
    								//обновляем время регистрации торрента в базе
    								Database::setNewDate($id, $date);
    								//отправляем уведомлении о новом торренте
    								$message = $name.' обновлён.';
    								Notification::sendNotification('notification', $date_str, $tracker, $message);
                                }
                                else
                                {
                                    //устанавливаем варнинг
        							if (casstudio::$warning == NULL)
        							{
        								casstudio::$warning = TRUE;
        								Errors::setWarnings($tracker, 'not_available');
        							}
        							//останавливаем процесс выполнения, т.к. не может работать без кук
        							casstudio::$exucution = FALSE;
                                }
							}
						}
						else
						{
							//устанавливаем варнинг
							if (casstudio::$warning == NULL)
							{
								casstudio::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							casstudio::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (casstudio::$warning == NULL)
						{
							casstudio::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						casstudio::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (casstudio::$warning == NULL)
					{
						casstudio::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					casstudio::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (casstudio::$warning == NULL)
				{
					casstudio::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				casstudio::$exucution = FALSE;
			}
		}
	}
}
?>