<?php
class pornolab
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
        		'url'            => 'http://pornolab.net/forum/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'pornolab.net', 'Content-length' => strlen($sess_cookie)),
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
            		'url'            => 'http://pornolab.net/forum/login.php',
            		'postfields'     => 'login_username='.$login.'&login_password='.$password.'&login=%C2%F5%EE%E4',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/Вы ввели неверное\/неактивное имя пользователя или неверный пароль/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					pornolab::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match('/bb_data=.+;/U', $page, $array))
				{
					pornolab::$sess_cookie = $array[0];
					Database::setCookie($tracker, pornolab::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					pornolab::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (pornolab::$warning == NULL)
					{
						pornolab::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					pornolab::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (pornolab::$warning == NULL)
				{
					pornolab::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				pornolab::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (pornolab::$warning == NULL)
			{
				pornolab::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			pornolab::$exucution = FALSE;
		}
	}

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		$cookie = Database::getCookie($tracker);
		if (pornolab::checkCookie($cookie))
		{
			pornolab::$sess_cookie = $cookie;
			//запускам процесс выполнения
			pornolab::$exucution = TRUE;
		}
		else
    		pornolab::getCookie($tracker);

		if (pornolab::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://pornolab.net/forum/viewtopic.php?t='.$torrent_id,
            		'cookie'         => pornolab::$sess_cookie,
            		'sendHeader'     => array('Host' => 'pornolab.net', 'Content-length' => strlen(pornolab::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<span title=\"Зарегистрирован\">\[ (.+) \]<\/span>/', $page, $array))
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
							$date = pornolab::dateStringToNum($array[1]);
							$date_str = pornolab::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'http://pornolab.net/forum/dl.php?t='.$torrent_id,
                                		'cookie'         => pornolab::$sess_cookie.'; bb_dl='.$torrent_id,
                                		'sendHeader'     => array('Host' => 'pornolab', 'Content-length' => strlen(pornolab::$sess_cookie.'; bb_dl='.$torrent_id)),
                                		'referer'        => 'http://pornolab.net/forum/viewtopic.php?t='.$torrent_id,
                                	)
                                );

								if ($auto_update)
								{
								    $name = Sys::getHeader('http://pornolab.net/forum/viewtopic.php?t='.$torrent_id);
								    //обновляем заголовок торрента в базе
                                    Database::setNewName($id, $name);
								}

								$message = $name.' обновлён.';
								$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
							}
						}
						else
						{
							//устанавливаем варнинг
							if (pornolab::$warning == NULL)
							{
								pornolab::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							pornolab::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (pornolab::$warning == NULL)
						{
							pornolab::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						pornolab::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (pornolab::$warning == NULL)
					{
						pornolab::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					pornolab::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (pornolab::$warning == NULL)
				{
					pornolab::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				pornolab::$exucution = FALSE;
			}
		}
	}
}
?>