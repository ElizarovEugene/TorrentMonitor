<?php
class booktracker
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
        		'encoding'       => 1,
        		'url'            => 'https://booktracker.org/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'booktracker.org', 'Content-length' => strlen($sess_cookie)),
        	)
        );

		if (preg_match('/login\.php\?logout=true/U', $result))
			return TRUE;
		else
			return FALSE;
	}


	public static function checkRule($data)
	{
		if (preg_match('/\D+/', $data))
			return FALSE;
		else
			return TRUE;
	}

	private static function dateStringToNum($data)
	{
		$date = date('Y-m-d H:i:s', strtotime($data));

		return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', booktracker::dateStringToNum($data));
		$arr = preg_split("/\s/", $data);
		$date = $arr[0] . ' ' . Sys::dateNumToString($arr[1]) . ' ' . $arr[2] . ' ' . $arr[3];
		
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
            		'encoding'       => 1,
            		'url'            => 'https://booktracker.org/login.php',
            		'postfields'     => 'login_username=' . $login . '&login_password=' . $password . '&login=%D0%92%D1%85%D0%BE%D0%B4',
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/profile\.php\?mode=register/', $page, $array))
				{
					//устанавливаем варнинг
					if (booktracker::$warning == NULL)
					{
						booktracker::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					booktracker::$exucution = FALSE;
				}
				else
				{
					//если подходят - получаем куки
					if (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
					{
						booktracker::$sess_cookie = $array[1][0];
                        Database::setCookie($tracker, booktracker::$sess_cookie);
						//запускам процесс выполнения, т.к. не может работать без кук
						booktracker::$exucution = TRUE;
					}
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (booktracker::$warning == NULL)
				{
					booktracker::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				booktracker::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (booktracker::$warning == NULL)
			{
				booktracker::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			booktracker::$exucution = FALSE;
		}
	}

	public static function main($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (booktracker::checkCookie($cookie))
		{
			booktracker::$sess_cookie = $cookie;
			//запускам процесс выполнения
			booktracker::$exucution = TRUE;
		}
		else
    		booktracker::getCookie($tracker);

		if (booktracker::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'encoding'       => 1,
            		'url'            => 'https://booktracker.org/viewtopic.php?t='.$torrent_id,
            		'cookie'         => booktracker::$sess_cookie,
            		'sendHeader'     => array('Host' => 'booktracker.org', 'Content-length' => strlen(booktracker::$sess_cookie)),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/Зарегистрирован\s&nbsp;\s*\[\s<span title=".*["]>(.+)<\/span>/U', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания
							if (preg_match('/href=\"download.php\?id=(\d+)\"/', $page, $link))
							{
								//приводим дату к общему виду
								$date = booktracker::dateStringToNum($array[1]);
								$date_str = booktracker::dateNumToString($array[1]);
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$download_id = $link[1];

									$torrent = Sys::getUrlContent(
	                                	array(
	                                		'type'           => 'GET',
	                                		'returntransfer' => 1,
	                                		'url'            => 'https://booktracker.org/download.php?id='.$download_id,
	                                		'cookie'         => booktracker::$sess_cookie,
	                                		'sendHeader'     => array('Host' => 'booktracker.org', 'Content-length' => strlen(booktracker::$sess_cookie)),
	                                		'referer'        => 'https://booktracker.org/viewtopic.php?t='.$torrent_id,
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
								//устанавливаем варнинг
								if (booktracker::$warning == NULL)
                    			{
                    				booktracker::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'cant_find_dowload_link', $id);
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								booktracker::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (booktracker::$warning == NULL)
                			{
                				booktracker::$warning = TRUE;
                				Errors::setWarnings($tracker, 'cant_find_date', $id);
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							booktracker::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (booktracker::$warning == NULL)
            			{
            				booktracker::$warning = TRUE;
            				Errors::setWarnings($tracker, 'cant_find_date', $id);
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						booktracker::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (booktracker::$warning == NULL)
        			{
        				booktracker::$warning = TRUE;
        				Errors::setWarnings($tracker, 'cant_find_date', $id);
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					booktracker::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (booktracker::$warning == NULL)
    			{
    				booktracker::$warning = TRUE;
    				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				booktracker::$exucution = FALSE;
			}
		}
		booktracker::$warning = NULL;
	}
}
?>
