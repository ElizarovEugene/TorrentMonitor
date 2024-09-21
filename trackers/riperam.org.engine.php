<?php
class riperam
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	protected static $domain = 'http://riperam.org/';

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'returntransfer' => 1,
        		'encoding'       => 1,
        		'url'            => riperam::$domain,
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'riperam.org', 'Content-length' => strlen($sess_cookie)),
        	)
        );

		if (preg_match('/ucp\.php\?mode=logout/', $result))
			return TRUE;
		else
			return FALSE;
	}


	public static function checkRule($data)
	{
		if (preg_match('/\D\d\/+/', $data))
			return FALSE;
		else
			return TRUE;
	}

	private static function dateStringToNum($data)
	{
		$date = explode(', ', $data);
    	$date1 = explode(' ', $date[0]);
        $date2 = $date1[2].'-'.Sys::dateStringToNum($date1[1]).'-'.$date1[0];
        $date = $date2.' '.$date[1].':00';
        
        return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
    	$date1 = explode(', ', $data);
    	$date = $date1[0].' в '.$date1[1];
    	
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
            		'url'            => riperam::$domain.'ucp.php?mode=login',
            		'postfields'     => 'username='.$login.'&password='.$password.'&login=%C2%F5%EE%E4',
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/login\.php\?redirect=/', $page, $array))
				{
					//устанавливаем варнинг
					if (riperam::$warning == NULL)
					{
						riperam::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					riperam::$exucution = FALSE;
				}
				else
				{
					//если подходят - получаем куки
					if (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
					{
						riperam::$sess_cookie = implode('; ', $array[1]);
						Database::setCookie($tracker, riperam::$sess_cookie);
						//запускам процесс выполнения, т.к. не может работать без кук
						riperam::$exucution = TRUE;
					}
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (riperam::$warning == NULL)
				{
					riperam::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				riperam::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (riperam::$warning == NULL)
			{
				riperam::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			riperam::$exucution = FALSE;
		}
	}

	public static function main($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (riperam::checkCookie($cookie))
		{
			riperam::$sess_cookie = $cookie;
			//запускам процесс выполнения
			riperam::$exucution = TRUE;
		}
		else
    		riperam::getCookie($tracker);

		if (riperam::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'encoding'       => 1,
            		'url'            => riperam::$domain.$torrent_id,
            		'cookie'         => riperam::$sess_cookie,
            		'sendHeader'     => array('Host' => 'riperam.org', 'Content-length' => strlen(riperam::$sess_cookie)),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/\[ (\d{2} \D{6} \d{4}\, \d{2}\:\d{2}) \]/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания
							if (preg_match('/download\/file\.php\?id=(\d{6,8})/', $page, $link))
							{
								//приводим дату к общему виду
								$date = riperam::dateStringToNum($array[1]);
								$date_str = riperam::dateNumToString($array[1]);
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$download_id = $link[1];
                                    $torrent = Sys::getUrlContent(
	                                	array(
	                                		'type'           => 'GET',
	                                		'follow'         => 1,
	                                		'returntransfer' => 1,
	                                		'url'            => riperam::$domain.'download/file.php?id='.$download_id,
	                                		'cookie'         => riperam::$sess_cookie,
	                                		'sendHeader'     => array('Host' => 'riperam.org', 'Content-length' => strlen(riperam::$sess_cookie)),
	                                		'referer'        => riperam::$domain.$torrent_id.'.html',
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
    									$status = Sys::saveTorrent($tracker, $download_id, $torrent, $id, $hash, $message, $date_str, $name);

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
								if (riperam::$warning == NULL)
                    			{
                    				riperam::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'cant_find_dowload_link', $id);
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								riperam::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (riperam::$warning == NULL)
                			{
                				riperam::$warning = TRUE;
                				Errors::setWarnings($tracker, 'cant_find_date', $id);
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							riperam::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (riperam::$warning == NULL)
            			{
            				riperam::$warning = TRUE;
            				Errors::setWarnings($tracker, 'cant_find_date', $id);
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						riperam::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (riperam::$warning == NULL)
        			{
        				riperam::$warning = TRUE;
        				Errors::setWarnings($tracker, 'cant_find_date', $id);
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					riperam::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (riperam::$warning == NULL)
    			{
    				riperam::$warning = TRUE;
    				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				riperam::$exucution = FALSE;
			}
		}
		riperam::$warning = NULL;
	}
}