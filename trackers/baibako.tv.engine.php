<?php
class baibako
{
    protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	protected static $page;	
	protected static $log_page;
	protected static $xml_page;

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
		$result = Sys::getUrlContent(
			array(
				'type'           => 'POST',
				'returntransfer' => 1,
				'url'            => 'http://baibako.tv/',
				'cookie'         => baibako::$sess_cookie,
				'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen(baibako::$sess_cookie)),
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
		if (preg_match('/^[\.\+\s\'\`\:\;\-a-zA-Z0-9]+$/', $data))
			return TRUE;
		else
			return FALSE;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -3);
		$data = str_replace('-', ' ', $data);
		$arr = preg_split('/\s/', $data);
		
		$month = Sys::dateNumToString($arr[1]);
		$date = $arr[2].' '.$month.' '.$arr[0].' '.$arr[3];
		return $date;
	}

	//функция анализа эпизода
	private static function analysisEpisode($item)
	{
		preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
		if (isset($matches[0]))
		{
			$episode = $matches[0];
			$date = $item->pubDate;
			return array('episode'=>$episode, 'date'=>$date, 'link'=>(string)$item->link);
		}
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
		if (preg_match('/'.$name.'/i', (string)$item->title))
		{
			if ($hd == 1)
			{
				if (preg_match_all('/HD(TV)?720/', $item->title, $matches))
					return baibako::analysisEpisode($item);
			}
			elseif ($hd == 2)
			{
				if (preg_match_all('/HD(TV)?1080/', $item->title, $matches))
					return baibako::analysisEpisode($item);
			}
			else
			{
				if (preg_match_all('/x264/', $item->link, $matches))
					return baibako::analysisEpisode($item);
			}
		}
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
				    if (count($array[0]) == 3)
				    {
    					baibako::$sess_cookie = $array[1][0].'='.$array[2][0].' '.$array[1][1].'='.$array[2][1].' '.$array[1][2].'='.$array[2][2];
    					Database::setCookie($tracker, baibako::$sess_cookie);
    					//запускам процесс выполнения, т.к. не может работать без кук
    					baibako::$exucution = TRUE;
    				}
    				//иначе не верный логин или пароль
    				else
    				{
    					//устанавливаем варнинг
    					if (baibako::$warning == NULL)
            			{
            				baibako::$warning = TRUE;
            				Errors::setWarnings($tracker, 'credential_wrong');
            			}
    					//останавливаем выполнение цепочки
    					baibako::$exucution = FALSE;
    				}
				}
				//если не удалось получить никаких данных со страницы, значит трекер не доступен
				else
				{
					//устанавливаем варнинг
					if (baibako::$warning == NULL)
        			{
        				baibako::$warning = TRUE;
        				Errors::setWarnings($tracker, 'not_available');
        			}
					//останавливаем выполнение цепочки
					baibako::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (baibako::$warning == NULL)
    			{
    				baibako::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
				//останавливаем выполнение цепочки
				baibako::$exucution = FALSE;
			}

		}
		else
		{
			//устанавливаем варнинг
			if (baibako::$warning == NULL)
			{
				baibako::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем выполнение цепочки
			baibako::$exucution = FALSE;						
		}	
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp, $hash)
	{
		//проверяем небыло ли до этого уже ошибок
		if (empty(baibako::$exucution) || (baibako::$exucution))
		{
			//проверяем получена ли уже кука
			if (empty(baibako::$sess_cookie))
			{
        		$cookie = Database::getCookie($tracker);
        		if (baibako::checkCookie($cookie))
        		{
        			baibako::$sess_cookie = $cookie;
        			//запускам процесс выполнения
        			baibako::$exucution = TRUE;
        		}			
        		else
                    baibako::getCookie($tracker);
			}

			//проверяем получена ли уже RSS лента
			if ( ! baibako::$log_page)
			{
				if (baibako::$exucution)
				{
    				$credentials = Database::getCredentials('baibako.tv');
					//получаем страницу
			        $page_xml = Sys::getUrlContent(
			        	array(
			        		'type'           => 'POST',
			        		'returntransfer' => 1,
			        		'url'            => 'http://baibako.tv/rss2.php?feed=dl&passkey='.$credentials['passkey'],
			        		'cookie'         => baibako::$sess_cookie,
                            'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen(baibako::$sess_cookie)),
                            'convert'        => array('windows-1251', 'utf-8//IGNORE'),
			        	)
			        );

                    $page_xml = str_replace('<?xml version="1.0" encoding="windows-1251" ?>','<?xml version="1.0" encoding="utf-8"?>', $page_xml);
                    
					if ( ! empty($page_xml))
					{
					    $xml_page = str_replace(array("&amp;", "&"), array("&", "&amp;"), $page_xml);
						//читаем xml
						baibako::$xml_page = @simplexml_load_string($xml_page);
						//если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
						if ( ! baibako::$xml_page)
						{
							//устанавливаем варнинг
        					if (baibako::$warning == NULL)
                			{
                				baibako::$warning = TRUE;
                				Errors::setWarnings($tracker, 'rss_parse_false');
                			}
							//останавливаем выполнение цепочки
							baibako::$exucution = FALSE;
						}
						else
							baibako::$log_page = TRUE;
                    }
					else
					{
						//устанавливаем варнинг
    					if (baibako::$warning == NULL)
            			{
            				baibako::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
						//останавливаем выполнение цепочки
						baibako::$exucution = FALSE;
					}
				}
			}
        }
        
		//если выполнение цепочки не остановлено
		if (baibako::$exucution)
		{
			if ( ! empty(baibako::$xml_page))
			{
				//сбрасываем варнинг
				Database::clearWarnings($tracker);
				$nodes = array();
				foreach (baibako::$xml_page->channel->item AS $item)
				{
				    array_unshift($nodes, $item);
				}
				
				foreach ($nodes as $item)
				{
					$serial = baibako::analysis($name, $hd, $item);
					if ( ! empty($serial))
					{
						$episode = substr($serial['episode'], 4, 2);
						$season = substr($serial['episode'], 1, 2);
						$date_str = baibako::dateNumToString($serial['date']);
						
						if ( ! empty($ep))
						{
							if ($season == substr($ep, 1, 2) && $episode > substr($ep, 4, 2))
								$download = TRUE;
							elseif ($season > substr($ep, 1, 2) && $episode < substr($ep, 4, 2))
								$download = TRUE;
							else
								$download = FALSE;
						}
						elseif ($ep == NULL)
							$download = TRUE;
						else
							$download = FALSE;

						if ($download)
						{
							$amp = ($hd) ? 'HD' : NULL;
							//сохраняем торрент в файл
							$torrent = Sys::getUrlContent(
								array(
									'type'           => 'GET',
									'returntransfer' => 1,
									'url'            => $serial['link'],
									'cookie'         => baibako::$sess_cookie,
									'sendHeader'     => array('Host' => 'baibako.tv', 'Content-length' => strlen(baibako::$sess_cookie)),
								)
							);							
							$file = str_replace(' ', '.', $name).'.S'.$season.'E'.$episode.'.'.$amp;
							$episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
							$season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
							$message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
							$status = Sys::saveTorrent($tracker, $file, $torrent, $id, $hash, $message, $date_str);
								
							if ($status == 'add_fail' || $status == 'connect_fail' || $status == 'credential_wrong')
							{
							    $torrentClient = Database::getSetting('torrentClient');
							    Errors::setWarnings($torrentClient, $status);
							}

							//обновляем время регистрации торрента в базе
							Database::setNewDate($id, $serial['date']);
							//обновляем сведения о последнем эпизоде
							Database::setNewEpisode($id, $serial['episode']);
						}
					}
				}
            }
        }
	}
}
?>