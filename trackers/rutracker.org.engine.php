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
        		'url'            => 'http://rutracker.org/forum/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'rutracker.org', 'Content-length' => strlen($sess_cookie)),
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
            		'url'            => 'http://login.rutracker.org/forum/login.php',
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
				elseif (preg_match('/bb_data=(.+);/iU', $page, $array))
				{
					rutracker::$sess_cookie = 'bb_data='.$array[1].';';
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
						Errors::setWarnings($tracker, 'not_available');
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
					Errors::setWarnings($tracker, 'not_available');
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

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash)
	{
		$cookie = Database::getCookie($tracker);
		if (rutracker::checkCookie($cookie))
		{
			rutracker::$sess_cookie = $cookie;
			//запускам процесс выполнения
			rutracker::$exucution = TRUE;
		}
		else
    		rutracker::getCookie($tracker);

		if (rutracker::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://rutracker.org/forum/viewtopic.php?t='.$torrent_id,
            		'cookie'         => rutracker::$sess_cookie,
            		'sendHeader'     => array('Host' => 'rutracker.org', 'Content-length' => strlen(rutracker::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<span title=\"Когда зарегистрирован\">\[ (.+) \]<\/span>/', $page, $array))
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
							$date_str = $array[1];
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'http://dl.rutracker.org/forum/dl.php?t='.$torrent_id,
                                		'cookie'         => rutracker::$sess_cookie.'; bb_dl='.$torrent_id,
                                		'sendHeader'     => array('Host' => 'dl.rutracker.org', 'Content-length' => strlen(rutracker::$sess_cookie.'; bb_dl='.$torrent_id)),
                                		'referer'        => 'http://rutracker.org/forum/viewtopic.php?t='.$torrent_id,
                                	)
                                );
								Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash);
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
								//отправляем уведомлении о новом торренте
								$message = $name.' обновлён.';
								Notification::sendNotification('notification', rutracker::dateNumToString($date_str), $tracker, $message);
							}
						}
						else
						{
							//устанавливаем варнинг
							if (rutracker::$warning == NULL)
							{
								rutracker::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
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
							Errors::setWarnings($tracker, 'not_available');
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
						Errors::setWarnings($tracker, 'not_available');
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
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutracker::$exucution = FALSE;
			}
		}
	}
}
?>