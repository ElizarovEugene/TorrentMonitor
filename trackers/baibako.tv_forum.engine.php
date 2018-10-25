<?php
class baibako_f
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
				'url'            => 'http://baibako.tv/',
				'cookie'         => $sess_cookie,
				'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen($sess_cookie)),
				'convert'        => array('windows-1251', 'utf-8//IGNORE'),
			)
		);

		if (preg_match('/<a href=\"logout\.php\">Выход<\/a>/U', $result))
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
    	$data1 = explode(' ', $data);
    	if (strlen($data1[0]) == 1)
			$data1[0] = '0'.$data1[0];
    	$data3 = $data1[2].'-'.Sys::dateStringToNum($data1[1]).'-'.$data1[0];
    	$date = $data3.' '.$data1[4];		
    	
    	return $date;	
	}	
	
	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -3);
		$arr = preg_split('/\s/', $data);
		
		$month = Sys::dateNumToString($arr[1]);
		$date = $arr[0].' '.$month.' '.$arr[2].' '.$arr[4];
		return $date;
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
			
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'http://baibako.tv/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password.'&commit=%CF%F3%F1%F2%E8%F2%E5+%EC%E5%ED%FF',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match_all('/Set-Cookie: (\w*)=(\S*)/', $page, $array))
				{
				    if (count($array[0]) > 0)
				    {
    				    baibako_f::$sess_cookie = '';
    				    for ($i=0; $i<count($array[0]); $i++)
                        {
                            baibako_f::$sess_cookie .= $array[1][$i].'='.$array[2][$i];
			            }
    					Database::setCookie($tracker, baibako_f::$sess_cookie);
    					//запускам процесс выполнения, т.к. не может работать без кук
    					baibako_f::$exucution = TRUE;
    				}
    				//иначе не верный логин или пароль
    				else
    				{
    					//устанавливаем варнинг
    					if (baibako_f::$warning == NULL)
            			{
            				baibako_f::$warning = TRUE;
            				Errors::setWarnings($tracker, 'credential_wrong');
            			}
    					//останавливаем выполнение цепочки
    					baibako_f::$exucution = FALSE;
    				}
				}
				//если не удалось получить никаких данных со страницы, значит трекер не доступен
				else
				{
					//устанавливаем варнинг
					if (baibako_f::$warning == NULL)
        			{
        				baibako_f::$warning = TRUE;
        				Errors::setWarnings($tracker, 'cant_find_cookie');
        			}
					//останавливаем выполнение цепочки
					baibako_f::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (baibako_f::$warning == NULL)
    			{
    				baibako_f::$warning = TRUE;
    				Errors::setWarnings($tracker, 'cant_get_auth_page');
    			}
				//останавливаем выполнение цепочки
				baibako_f::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (baibako_f::$warning == NULL)
			{
				baibako_f::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем выполнение цепочки
			baibako_f::$exucution = FALSE;						
		}	
	}
	
	//основная функция
	public static function main($params)
	{
        extract($params);
		$cookie = Database::getCookie($tracker);
		if (baibako_f::checkCookie($cookie))
		{
			baibako_f::$sess_cookie = $cookie;
			//запускам процесс выполнения
			baibako_f::$exucution = TRUE;
		}
		else
    		baibako_f::getCookie($tracker);
    		
    		
		if (baibako_f::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://baibako.tv/details.php?id='.$torrent_id,
            		'cookie'         => baibako_f::$sess_cookie,
            		'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen(baibako_f::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<td align=\"left\" width=\"16\.6\%\"><b>(.*)<\/b><\/td>/', $page, $array))
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
							$date = baibako_f::dateStringToNum($array[1]);
							$date_str = baibako_f::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'POST',
                                		'returntransfer' => 1,
                                		'url'            => 'http://baibako.tv/download.php?id='.$torrent_id,
                                		'cookie'         => baibako_f::$sess_cookie,
                                		'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen(baibako_f::$sess_cookie)),
                                		'referer'        => 'http://baibako.tv/download.php?id='.$torrent_id,
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
							if (baibako_f::$warning == NULL)
							{
								baibako_f::$warning = TRUE;
								Errors::setWarnings($tracker, 'cant_find_date', $id);
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							baibako_f::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (baibako_f::$warning == NULL)
						{
							baibako_f::$warning = TRUE;
							Errors::setWarnings($tracker, 'cant_find_date', $id);
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						baibako_f::$exucution = FALSE;
					}
                }
			}
			else
			{
				//устанавливаем варнинг
				if (baibako_f::$warning == NULL)
				{
					baibako_f::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				baibako_f::$exucution = FALSE;
			}
		}
		baibako_f::$warning = NULL;		
	}
}
?>