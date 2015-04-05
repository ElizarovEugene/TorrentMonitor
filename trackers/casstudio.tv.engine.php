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

		if (preg_match('/\.\/ucp\.php\?mode=logout/', $result))
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
	    if (strstr($data, 'Сегодня') || strstr($data, 'Вчера'))
	    {
	        $pieces = explode(',', $data);
	        if ($pieces[0] == 'Сегодня')
	            $timestamp = strtotime('now');
            if ($pieces[0] == 'Вчера')
	            $timestamp = strtotime('-1 day');
	        $date = date('Y-m-d', $timestamp);
	        $dateTime = $date.$pieces[1].':00';

	        return $dateTime;
	    }
        elseif (preg_match('/\d{1,2} \D* \d{4}, \d{2}:\d{2}/', $data))
	    {
			$pieces = explode(' ', $data);
			$month = Sys::dateStringToNum(substr($pieces[1], 0, 6));
			if (strlen($pieces[0]) == 1)
			    $pieces[0] = '0'.$pieces[0];
			
			$year = substr($pieces[2], 0, -1);
			$date = $year.'-'.$month.'-'.$pieces[0];
			$time = $pieces[3].':00';
			$dateTime = $date.' '.$time;

			return $dateTime;
	    }	    
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
            		'url'            => 'http://casstudio.tv/ucp.php?mode=login',
            		'postfields'     => 'login=Вход&username='.$login.'&password='.$password.'&x=0&y=0',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/Вы ввели неверное имя пользователя. Проверьте его и попробуйте ввести ещё раз./', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					casstudio::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
				{
					casstudio::$sess_cookie = $array[1][3].'; '.$array[1][4].'; '.$array[1][5];
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
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
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
            		'url'            => 'http://casstudio.tv/viewtopic.php?t='.$torrent_id,
            		'cookie'         => casstudio::$sess_cookie,
            		'sendHeader'     => array('Host' => 'casstudio.tv', 'Content-length' => strlen(casstudio::$sess_cookie)),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<b>Добавлен<\/b>: <span class=\"my_tt\" title=\"(.*)\">(.*)<\/span>/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[2]))
					{
						//если дата не равна ничему
						if ( ! empty($array[2]))
						{
							//сбрасываем варнинг
							Database::clearWarnings($tracker);
							//приводим дату к общему виду
                            $date = casstudio::dateStringToNum($array[2]);
							$date_str = casstudio::dateNumToString($date);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
							    if (preg_match('/\.\/download\/file\.php\?id=(.*)\" title=\"Скачать торрент\"/', $page, $link))
							    {
    								//сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                    	array(
                                    		'type'           => 'POST',
                                    		'returntransfer' => 1,
                                    		'url'            => 'http://casstudio.tv/download/file.php?id='.$link[1],
                                    		'cookie'         => casstudio::$sess_cookie,
                                    		'sendHeader'     => array('Host' => 'casstudio.tv', 'Content-length' => strlen(casstudio::$sess_cookie)),
                                    		'referer'        => 'http://casstudio.tv/viewtopic.php?t='.$link[1],
                                        	)
                                    );

    								if ($auto_update)
    								{
    								    $name = Sys::getHeader('http://casstudio.tv/viewtopic.php?t='.$torrent_id);
    								    //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
    								}

    								$message = $name.' обновлён.';
    								$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
    								//обновляем время регистрации торрента в базе
    								Database::setNewDate($id, $date);
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