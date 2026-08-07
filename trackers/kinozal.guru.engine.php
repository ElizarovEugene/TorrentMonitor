<?php
class kinozalguru
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'returntransfer' => 1,
        		'url'            => 'https://kinozal.guru',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'kinozal.guru', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/<a href=\'\/userdetails\.php\?id=\d*\'>.*<\/a>/U', $result))
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
	    if (strstr($data, 'сегодня') || strstr($data, 'вчера') || strstr($data, 'сейчас'))
	    {
	        $pieces = explode(' ', $data);
	        if ($pieces[0] == 'вчера')
	            $timestamp = strtotime('-1 day');
	        else
	            $timestamp = strtotime('now');
	        $date = date('Y-m-d', $timestamp);
	        if (strstr($data, 'сейчас'))
	            $time = date('H:i').':00';
            else
	            $time = $pieces[2].':00';
	        $dateTime = $date.' '.$time;

	        return $dateTime;
	    }
	    elseif (preg_match('/\d{1,2} \D* \d{4} в \d{2}:\d{2}/', $data))
	    {
			$pieces = explode(' ', $data);
			$month = Sys::dateStringToNum(substr($pieces[1], 0, 6));
			if (strlen($pieces[0]) == 1)
			    $pieces[0] = '0'.$pieces[0];
			$date = $pieces[2].'-'.$month.'-'.$pieces[0];
			$time = $pieces[4].':00';
			$dateTime = $date.' '.$time;

			return $dateTime;
	    }
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
	    if (strstr($data, 'сегодня') || strstr($data, 'вчера'))
	    {
	        $pieces = explode(' ', $data);
	        if ($pieces[0] == 'вчера')
	            $timestamp = strtotime('-1 day');
	        else
	            $timestamp = strtotime('now');
	        $day = date('d', $timestamp);
			$month = Sys::dateNumToString(date('m', $timestamp));
			$year = date('Y', $timestamp);
	        $dateTime = $day.' '.$month.' '.$year.' в '.$pieces[2];
	        return $dateTime;
	    }
	    elseif (strstr($data, 'сейчас'))
	    {
	        $timestamp = strtotime('now');
	        $day = date('d', $timestamp);
			$month = Sys::dateNumToString(date('m', $timestamp));
			$year = date('Y', $timestamp);
			$time = date('H:i').':00';
	        $dateTime = $day.' '.$month.' '.$year.' в '.$time;
	        return $dateTime;
        }
	   	else
			return $data;
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
            		'url'            => 'https://kinozal.guru/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password.'&returnto=',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/Не верно указан пароль/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalguru::$exucution = FALSE;
				}
				//проверяем нет ли блокировки
				if (preg_match('/Превышен лимит попыток входа в профиль <br>Попробуйте через 2 часа/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'limit');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalguru::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.+);/iU', $page, $array))
				{
					kinozalguru::$sess_cookie = $array[1][0].'; '.$array[1][1].';';
					Database::setCookie($tracker, kinozalguru::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					kinozalguru::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (kinozalguru::$warning == NULL)
					{
						kinozalguru::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_cookie');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalguru::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (kinozalguru::$warning == NULL)
				{
					kinozalguru::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozalguru::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozalguru::$warning == NULL)
			{
				kinozalguru::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozalguru::$exucution = FALSE;
		}
	}

    public static function work($titlearray, $array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update, &$return)
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
				$date = kinozalguru::dateStringToNum($array[1]);
				$date_str = kinozalguru::dateNumToString($array[1]);
				//если даты не совпадают, перекачиваем торрент
				if ($date > $timestamp)
				{
					//сохраняем торрент в файл
                    $torrent = Sys::getUrlContent(
                    	array(
                    		'type'           => 'GET',
                    		'returntransfer' => 1,
                    		'url'            => 'https://kinozal.guru/download.php?id='.$torrent_id,
                    		'cookie'         => kinozalguru::$sess_cookie,
                    		'sendHeader'     => array('Host' => 'kinozal.guru', 'Content-length' => strlen(kinozalguru::$sess_cookie)),
                    		'referer'        => 'https://kinozal.guru/details.php?id='.$torrent_id,
                    	)
                    );
					if (preg_match('/<a href=\'\/pay_mode\.php\#tcounter\' class=sbab>/', $torrent))
					{
        				//устанавливаем варнинг
        				if (kinozalguru::$warning == NULL)
        				{
        					kinozalguru::$warning = TRUE;
        					Errors::setWarnings($tracker, 'max_torrent');
        				}
        				//останавливаем процесс выполнения
        				kinozalguru::$exucution = FALSE;
					}
					else
					{
                        if (Sys::checkTorrentFile($torrent))
                        {
        					if ($auto_update)
    						    $name = Sys::parseHeader($tracker, $titlearray[1]);

        					$message = $name.' обновлён.';
        					$saved = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str, $name);

        					if ($saved)
        					{
        					    if ($auto_update)
        					        //обновляем заголовок торрента в базе
        					        $return[$id]['name'] = $name;
        					    //обновляем время регистрации торрента в базе
        					    $return[$id]['timestamp'] = $date;
							    //сбрасываем варнинг
							    Database::clearWarnings($tracker);
        					    $return[$id]['error'] = 0;
        					}
        					else
        					    Errors::setWarnings($tracker, 'save_file_fail', $id);
        				}
        				else
                            Errors::setWarnings($tracker, 'torrent_file_fail', $id);
    				}
				}
				$return[$id]['error'] = 0;
			}
			else
			{
				//устанавливаем варнинг
				if (kinozalguru::$warning == NULL)
				{
					kinozalguru::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_find_date', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozalguru::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozalguru::$warning == NULL)
			{
				kinozalguru::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_find_date', $id);
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozalguru::$exucution = FALSE;
		}
    }

	//формируем параметры "проверочного" запроса для curl_multi (резолв куки последовательный, как и раньше)
	public static function getRequestParams($params)
	{
		extract($params);
		$cookie = Database::getCookie($tracker);
		if (kinozalguru::checkCookie($cookie))
		{
			kinozalguru::$sess_cookie = $cookie;
			//запускам процесс выполнения
			kinozalguru::$exucution = TRUE;
		}
		else
    		kinozalguru::getCookie($tracker);

		if ( ! kinozalguru::$exucution)
		{
			kinozalguru::$warning = NULL;
			return array('url' => NULL);
		}

		$url = 'https://kinozal.guru/details.php?id='.$torrent_id;

		$options = array(
			CURLOPT_COOKIE => kinozalguru::$sess_cookie,
		);

		if (Sys::checkCurlVersion() == 'old')
		{
			$header = array();
			foreach (array('Host' => 'kinozal.guru', 'Content-length' => strlen(kinozalguru::$sess_cookie)) as $k => $v)
				$header[] = $k.': '.$v."\r\n";
			$options[CURLOPT_HTTPHEADER] = $header;
		}

		return array(
			'url'     => $url,
			'options' => $options + Sys::getProxyOptions($url),
		);
	}

	//разбираем полученную страницу, возвращаем изменения для batchUpdateTorrents или null
	public static function parse($params, $page)
	{
		extract($params);
		$return = NULL;

		$page = iconv('windows-1251', 'utf-8//IGNORE', $page);

		if ( ! empty($page))
		{
			preg_match('/(<title>.*<\/title>)/', $page, $titlearray);
			//ищем на странице дату регистрации торрента
			if (preg_match('/<li>Обновлен<span class=\"floatright green n\">(.*)<\/span><\/li>/', $page, $array))
				kinozalguru::work($titlearray, $array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update, $return);
			elseif (preg_match('/<li>Залит<span class=\"floatright green n\">(.*)<\/span><\/li>/', $page, $array))
			    kinozalguru::work($titlearray, $array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update, $return);
			else
			{
				//устанавливаем варнинг
				if (kinozalguru::$warning == NULL)
				{
					kinozalguru::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_find_date', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без даты
				kinozalguru::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozalguru::$warning == NULL)
			{
				kinozalguru::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozalguru::$exucution = FALSE;
		}

		kinozalguru::$warning = NULL;
		return $return;
	}
}
?>
