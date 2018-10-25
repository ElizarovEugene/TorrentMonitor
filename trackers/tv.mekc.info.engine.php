<?php
class mekc
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
        		'url'            => 'http://tv.mekc.info/',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'tv.mekc.info', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/<a href=\"http:\/\/tv\.mekc\.info\/userdetails\.php\?id=.*\">.*<\/a>/U', $result))
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
	
	//функция преобразования даты
	private static function dateNumToString($data)
	{
    	$data1 = explode(' ', $data);
    	$data2 = explode('-', $data1[0]);

    	$data3 = $data2[2].' '.Sys::dateNumToString($data2[1]).' '.$data2[0];
    	$date = $data3.' в '.$data[1];		
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
            		'url'            => 'http://tv.mekc.info/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password.'&x=0&y=0',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/<b>Ошибка входа<\/b>/', $page, $array))
				{
					//устанавливаем варнинг
					if (mekc::$warning == NULL)
					{
						mekc::$warning = TRUE;
						Errors::setWarnings($tracker, 'credential_wrong');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					mekc::$exucution = FALSE;
				}
				else
				{
					//если подходят - получаем куки
					if (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
					{
						mekc::$sess_cookie = implode('; ', $array[1]);
						Database::setCookie($tracker, mekc::$sess_cookie);
						//запускам процесс выполнения, т.к. не может работать без кук
						mekc::$exucution = TRUE;
					}
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (mekc::$warning == NULL)
				{
					mekc::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				mekc::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (mekc::$warning == NULL)
			{
				mekc::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			mekc::$exucution = FALSE;
		}
	}
	
	public static function main($params)
	{
    	extract($params);
		$cookie = Database::getCookie($tracker);
		if (mekc::checkCookie($cookie))
		{
			mekc::$sess_cookie = $cookie;
			//запускам процесс выполнения
			mekc::$exucution = TRUE;
		}			
		else
    		mekc::getCookie($tracker);

		if (mekc::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://tv.mekc.info/details.php?id='.$torrent_id,
            		'cookie'         => mekc::$sess_cookie,
            		'sendHeader'     => array('Host' => 'tv.mekc.info', 'Content-length' => strlen(mekc::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td style=\"background:#dde1e2;border:2px solid #f4f4f4;\" valign=\"top\" align=\"left\">(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания		
							if (preg_match('/(http:\/\/tv\.mekc\.info\/download\/give_it_to_meh\.php\?id=\d{3,6}&passkey=.*)\">/', $page, $links))
							{
							    //ссылка
							    $link = str_replace('give_it_to_meh', 'download', $links[1]);
								//сбрасываем варнинг
								Database::clearWarnings($tracker);
								//приводим дату к общему виду
								$date = $array[1];
								$date_str = mekc::dateNumToString($array[1]);
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//сохраняем торрент в файл
									$torrent = Sys::getUrlContent(
	                                	array(
	                                		'type'           => 'GET',
	                                		'returntransfer' => 1,
	                                		'url'            => $link,
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
                                    }
                                    else
                                        Errors::setWarnings($tracker, 'torrent_file_fail', $id);
								}
							}
							else
							{
								//устанавливаем варнинг
								if (mekc::$warning == NULL)
                    			{
                    				mekc::$warning = TRUE;
                    				Errors::setWarnings($tracker, 'cant_find_dowload_link', $id);
                    			}
                    			//останавливаем процесс выполнения, т.к. не может работать без кук
								mekc::$exucution = FALSE;
							}
						}
						else
						{
							//устанавливаем варнинг
							if (mekc::$warning == NULL)
                			{
                				mekc::$warning = TRUE;
                				Errors::setWarnings($tracker, 'cant_find_date', $id);
                			}
                			//останавливаем процесс выполнения, т.к. не может работать без кук
							mekc::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (mekc::$warning == NULL)
            			{
            				mekc::$warning = TRUE;
            				Errors::setWarnings($tracker, 'cant_find_date', $id);
            			}
            			//останавливаем процесс выполнения, т.к. не может работать без кук
						mekc::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (mekc::$warning == NULL)
        			{
        				mekc::$warning = TRUE;
        				Errors::setWarnings($tracker, 'cant_find_date', $id);
        			}
        			//останавливаем процесс выполнения, т.к. не может работать без кук
					mekc::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (mekc::$warning == NULL)
    			{
    				mekc::$warning = TRUE;
    				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
    			}
    			//останавливаем процесс выполнения, т.к. не может работать без кук
				mekc::$exucution = FALSE;
			}
		}
		mekc::$warning = NULL;
	}
}
?>