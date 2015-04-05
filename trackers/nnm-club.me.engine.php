<?php
class nnmclub
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
        		'url'            => 'http://nnm-club.me/forum/index.php',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'nnm-club.me', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/class=\"mainmenu\">Выход [ .* ]<\/a>/U', $result))
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
		$monthes = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
		$month = substr($data, 3, 6);
		$date = preg_replace('/(\d\d)\s(\d\d)\s(\d\d\d\d)/', '$3-$2-$1',str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		$date = date('Y-m-d H:i:s', strtotime($date));
	
		return $date;
	}
	
	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$date = substr($data, 0, -3);
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
            		'url'            => 'http://nnm-club.me/forum/login.php',
            		'postfields'     => 'username='.$login.'&password='.$password.'&login=%C2%F5%EE%E4',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );
			
			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/login\.php\?redirect=/', $page, $array))
				{
					//устанавливаем варнинг
					if (nnmclub::$warning == NULL)
					{
						nnmclub::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					nnmclub::$exucution = FALSE;
				}
				else
				{
					//если подходят - получаем куки
					if (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
					{
						nnmclub::$sess_cookie = implode('; ', $array[1]);
						Database::setCookie($tracker, nnmclub::$sess_cookie);
						//запускам процесс выполнения, т.к. не может работать без кук
						nnmclub::$exucution = TRUE;
					}
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (nnmclub::$warning == NULL)
				{
					nnmclub::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				nnmclub::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (nnmclub::$warning == NULL)
			{
				nnmclub::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			nnmclub::$exucution = FALSE;
		}
	}
	
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		$cookie = Database::getCookie($tracker);
		if (nnmclub::checkCookie($cookie))
		{
			nnmclub::$sess_cookie = $cookie;
			//запускам процесс выполнения
			nnmclub::$exucution = TRUE;
		}			
		else
    		nnmclub::getCookie($tracker);
		
		if (nnmclub::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://nnm-club.me/forum/viewtopic.php?t='.$torrent_id,
            		'cookie'         => nnmclub::$sess_cookie,
            		'sendHeader'     => array('Host' => 'nnm-club.me', 'Content-length' => strlen(nnmclub::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td class=\"genmed\">&nbsp;(\d{2}\s\D{6}\s\d{4}\s\d{2}:\d{2}:\d{2})<\/td>/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания		
							if (preg_match('/download\.php\?id=(\d{6,8})/', $page, $link))
							{
								//сбрасываем варнинг
								Database::clearWarnings($tracker);
								//приводим дату к общему виду
								$date = nnmclub::dateStringToNum($array[1]);
								$date_str = $array[1];
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$download_id = $link[1];
									preg_match('/userid(.*);/U', nnmclub::$sess_cookie, $arr);
                                    $uid = $arr[1];
									
									$torrent = Sys::getUrlContent(
	                                	array(
	                                		'type'           => 'GET',
	                                		'returntransfer' => 1,
	                                		'url'            => 'http://nnm-club.ws/download.php?csid=&uid='.$uid.'&id='.$download_id,
	                                		'cookie'         => nnmclub::$sess_cookie,
	                                		'sendHeader'     => array('Host' => 'nnm-club.ws', 'Content-length' => strlen(nnmclub::$sess_cookie)),
	                                		'referer'        => 'http://nnm-club.me/forum/viewtopic.php?t='.$torrent_id,
	                                	)
	                                );
									$message = $name.' обновлён.';
									$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
    								//обновляем время регистрации торрента в базе
									Database::setNewDate($id, $date);
									
									if ($auto_update)
    								{
    								    $name = Sys::getHeader('http://nnm-club.me/forum/viewtopic.php?t='.$torrent_id);
    								    //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
    								}
								}
							}
							else
							{
								//устанавливаем варнинг
								if (nnmclub::$warning == NULL)
                    			{
                    				nnmclub::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'not_available');
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								nnmclub::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (nnmclub::$warning == NULL)
                			{
                				nnmclub::$warning = TRUE;
                				Errors::setWarnings($tracker, 'not_available');
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							nnmclub::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (nnmclub::$warning == NULL)
            			{
            				nnmclub::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						nnmclub::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (nnmclub::$warning == NULL)
        			{
        				nnmclub::$warning = TRUE;
        				Errors::setWarnings($tracker, 'not_available');
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					nnmclub::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (nnmclub::$warning == NULL)
    			{
    				nnmclub::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				nnmclub::$exucution = FALSE;
			}
		}
	}
}
?>