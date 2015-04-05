<?php
class novafilm
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
				'url'            => 'http://novafilm.tv',
				'cookie'         => $sess_cookie,
				'sendHeader'     => array('Host' => 'novafilm.tv', 'Content-length' => strlen($sess_cookie)),
			)
		);

		if (preg_match('/<p>Здравствуйте, <a href=\"\/user\/.*">.*<\/a>/U', $result))
			return TRUE;
		else
			return FALSE;		  
	}

	public static function checkRule($data)
	{
		if (preg_match('/^[\.\+\s\'\`\:\;\-a-zA-Z0-9]+$/', $data))
			return TRUE;
		else
			return FALSE;
	}

	//функция преобразования даты из строки
	private static function dateStringToNum($data)
	{
		$data = substr($data, 5);
		$data = substr($data, 0, -6);
		
		$monthes = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$month = substr($data, 3, 3);
		$data = preg_replace('/(\d\d)-(\d\d)-(\d\d)/', '$3-$2-$1', str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		
		$data = preg_split('/\s/', $data);		
		$date = $data[2].'-'.$data[1].'-'.$data[0].' '.$data[3];
		return $date;
	}
	
	//функция преобразования даты в строку
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -3);
		$data = str_replace('-', ' ', $data);
		$arr = preg_split('/\s/', $data);
		
		$month = Sys::dateNumToString($arr[1]);
		$date = $arr[2].' '.$month.' '.$arr[0].' '.$arr[3];
		return $date;
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
		if (preg_match('/'.$name.'/i', $item->title))
		{
			if ($hd == 1)
			{
				if (preg_match_all('/720/', $item->link, $matches))
				{
					preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
					if (isset($matches[0]))
					{
						$episode = $matches[0];
						$date = novafilm::dateStringToNum($item->pubDate);
						return array('episode'=>$episode, 'date'=>$date, 'link'=>(string)$item->link);
					}
				}
			}
			elseif ($hd == 2)
			{
				if (preg_match_all('/1080/', $item->link, $matches))
				{
					preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
					if (isset($matches[0]))
					{
						$episode = $matches[0];
						$date = novafilm::dateStringToNum($item->pubDate);
						return array('episode'=>$episode, 'date'=>$date, 'link'=>(string)$item->link);
					}
				}
			}
			else
			{
				if (preg_match_all('/^(?!(.*720|.*1080))/', $item->link, $matches))
				{
					preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
					if (isset($matches[0]))
					{
						$episode = $matches[0];
						$date = novafilm::dateStringToNum($item->pubDate);
						return array('episode'=>$episode, 'date'=>$date, 'link'=>(string)$item->link);
					}
				}
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
            		'url'            => 'http://novafilm.tv/auth/login',
            		'postfields'     => 'returnto=/&username='.$login.'&password='.$password.'&login=Хочу войти!',
            	)
            );
            
			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match_all('/Set-Cookie: (\w*)=(\S*)/', $page, $array))
				{
					novafilm::$sess_cookie = $array[1][2].'='.$array[2][2];
					Database::setCookie($tracker, novafilm::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					novafilm::$exucution = TRUE;
				}
				//проверяем нет ли сообщения о неправильном логине/пароле
				elseif (preg_match('/\/do\/recover/', $page, $out))
				{
					//устанавливаем варнинг
					if (novafilm::$warning == NULL)
        			{
        				novafilm::$warning = TRUE;
        				Errors::setWarnings($tracker, 'credential_wrong');
        			}
					//останавливаем выполнение цепочки
					novafilm::$exucution = FALSE;
				}
				//если не удалось получить никаких данных со страницы, значит трекер не доступен
				else
				{
					//устанавливаем варнинг
					if (novafilm::$warning == NULL)
        			{
        				novafilm::$warning = TRUE;
        				Errors::setWarnings($tracker, 'not_available');
        			}
					//останавливаем выполнение цепочки
					novafilm::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (novafilm::$warning == NULL)
    			{
    				novafilm::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
				//останавливаем выполнение цепочки
				novafilm::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (novafilm::$warning == NULL)
			{
				novafilm::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем выполнение цепочки
			novafilm::$exucution = FALSE;						
		}	
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp, $hash)
	{
		//проверяем небыло ли до этого уже ошибок
		if (empty(novafilm::$exucution) || (novafilm::$exucution))
		{
			//проверяем получена ли уже кука
			if (empty(novafilm::$sess_cookie))
			{
        		$cookie = Database::getCookie($tracker);
        		if (novafilm::checkCookie($cookie))
        		{
        			novafilm::$sess_cookie = $cookie;
        			//запускам процесс выполнения
        			novafilm::$exucution = TRUE;
        		}			
        		else
            		novafilm::getCookie($tracker);
			}
			
			//проверяем получена ли уже RSS лента
			if ( ! novafilm::$log_page)
			{
				if (novafilm::$exucution)
				{
					//получаем страницу
			        novafilm::$page = Sys::getUrlContent(
			        	array(
			        		'type'           => 'GET',
			        		'returntransfer' => 1,
			        		'url'            => 'http://novafilm.tv/rss/rssd.xml',
			        	)
			        );
			        
					if ( ! empty(novafilm::$page))
					{
						//читаем xml
						novafilm::$xml_page = @simplexml_load_string(novafilm::$page);
						//если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
						if ( ! novafilm::$xml_page)
						{
							//устанавливаем варнинг
        					if (novafilm::$warning == NULL)
                			{
                				novafilm::$warning = TRUE;
                				Errors::setWarnings($tracker, 'rss_parse_false');
                			}
							//останавливаем выполнение цепочки
							novafilm::$exucution = FALSE;
						}
						else
							novafilm::$log_page = TRUE;
					}
					else
					{
						//устанавливаем варнинг
    					if (novafilm::$warning == NULL)
            			{
            				novafilm::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
						//останавливаем выполнение цепочки
						novafilm::$exucution = FALSE;
					}
				}
			}
		}
	
		//если выполнение цепочки не остановлено
		if (novafilm::$exucution)
		{
			if ( ! empty(novafilm::$xml_page))
			{
				//сбрасываем варнинг
				Database::clearWarnings($tracker);
				$nodes = array();
				foreach (novafilm::$xml_page->channel->item AS $item)
				{
				    array_unshift($nodes, $item);
				}

				foreach ($nodes as $item)
				{
					$serial = novafilm::analysis($name, $hd, $item);
					if ( ! empty($serial))
					{
						$episode = substr($serial['episode'], 4, 2);
						$season = substr($serial['episode'], 1, 2);
						$date_str = novafilm::dateNumToString($serial['date']);
						
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
									'type'           => 'POST',
									'returntransfer' => 1,
									'url'            => $serial['link'],
									'cookie'         => novafilm::$sess_cookie,
									'sendHeader'     => array('Host' => 'novafilm.tv', 'Content-length' => strlen(novafilm::$sess_cookie)),
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