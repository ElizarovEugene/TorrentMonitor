<?php
class rutracker
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
        		'url'            => 'https://rutracker.org/forum/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'rutracker.org', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/profile\.php\?mode=viewprofile/', $result))
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
            		'url'            => 'https://rutracker.org/forum/login.php',
                        'sendHeader'     => array('Host' => 'rutracker.org', 'Content-length' => strlen($login.'&login_password='.$password.'&login=%C2%F5%EE%E4')),
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
					rutracker::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match('/bb_session=.+;/U', $page, $array))
				{
					rutracker::$sess_cookie = $array[0];
					Database::setCookie($tracker, rutracker::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (rutracker::$warning == NULL)
					{
						rutracker::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_cookie');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (rutracker::$warning == NULL)
				{
					rutracker::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutracker::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (rutracker::$warning == NULL)
			{
				rutracker::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			rutracker::$exucution = FALSE;
		}
	}

	//формируем параметры "проверочного" запроса для curl_multi (резолв куки последовательный, как и раньше)
	public static function getRequestParams($params)
	{
		extract($params);
		$cookie = Database::getCookie($tracker);
		if (rutracker::checkCookie($cookie))
		{
			rutracker::$sess_cookie = $cookie;
			//запускам процесс выполнения
			rutracker::$exucution = TRUE;
		}
		else
    		rutracker::getCookie($tracker);

		if ( ! rutracker::$exucution)
		{
			rutracker::$warning = NULL;
			return array('url' => NULL);
		}

		$url = 'https://rutracker.org/forum/viewtopic.php?t='.$torrent_id;

		$options = array(
			CURLOPT_POST   => 1,
			CURLOPT_COOKIE => rutracker::$sess_cookie,
		);

		if (Sys::checkCurlVersion() == 'old')
		{
			$header = array();
			foreach (array('Host' => 'rutracker.org', 'Content-length' => strlen(rutracker::$sess_cookie)) as $k => $v)
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
			//ищем на странице дату регистрации торрента
			if (preg_match('/<td style=\"width: 70%; padding: 5px;\">\n\t{2}<ul class=\"inlined middot-separated\">\n\t{3}<li>(.*)<\/li>/', $page, $array))
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
						$date = rutracker::dateStringToNum($array[1]);
						$date_str = rutracker::dateNumToString($array[1]);
						$return = array($id => array('error' => 0));
						//если даты не совпадают, перекачиваем торрент
						if ($date != $timestamp)
						{
							//сохраняем торрент в файл
                            $torrent = Sys::getUrlContent(
                            	array(
                            		'type'           => 'POST',
                            		'returntransfer' => 1,
                            		'url'            => 'https://rutracker.org/forum/dl.php?t='.$torrent_id,
                            		'cookie'         => rutracker::$sess_cookie.'; bb_dl='.$torrent_id,
                            		'sendHeader'     => array('Host' => 'rutracker.org', 'Content-length' => strlen(rutracker::$sess_cookie.'; bb_dl='.$torrent_id)),
                            		'referer'        => 'https://rutracker.org/forum/viewtopic.php?t='.$torrent_id,
                            	)
                            );

                            if (Sys::checkTorrentFile($torrent))
                            {
								if ($auto_update)
								    $name = Sys::parseHeader($tracker, $page);

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
								    $return[$id]['closed'] = 0;
								}
								else
								    Errors::setWarnings($tracker, 'save_file_fail', $id);
                            }
                            else
                                Errors::setWarnings($tracker, 'torrent_file_fail', $id);
						}
					}
					else
					{
						//устанавливаем варнинг
						if (rutracker::$warning == NULL)
						{
							rutracker::$warning = TRUE;
							Errors::setWarnings($tracker, 'cant_find_date', $id);
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						rutracker::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (rutracker::$warning == NULL)
					{
						rutracker::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_date', $id);
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutracker::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (rutracker::$warning == NULL)
				{
					rutracker::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_find_date', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutracker::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (rutracker::$warning == NULL)
			{
				rutracker::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			rutracker::$exucution = FALSE;
		}

		rutracker::$warning = NULL;
		return $return;
	}
}
?>
