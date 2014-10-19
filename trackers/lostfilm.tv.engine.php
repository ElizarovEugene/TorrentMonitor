<?php
class lostfilm
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	protected static $page;	
	protected static $log_page;
	protected static $xml_page;
	
	//получаем куки для доступа к сайту
	private static function login($type, $login, $password)
	{
		if ($type == 'simple')
		{
			$url = 'http://lostfilm.tv/useri.php';
			$postfields = 'FormLogin='.$login.'&FormPassword='.$password.'&module=1&repage=user&act=login';
		}
		if ($type == 'hard')
		{
			$url = 'http://login1.bogi.ru/login.php?referer=http%3A%2F%2Fwww.lostfilm.tv%2F';
			$postfields = 'login='.$login.'&password='.$password.'&module=1&target=http%3A%2F%2Flostfilm.tv%2F&repage=user&act=login';
		}

        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'header'         => 1,
        		'returntransfer' => 1,
        		'url'            => $url,
        		'postfields'     => $postfields,
        	)
        );

		return $result;
	}
	
	//получаем куки для доступа к сайту
	private static function getCookies($tracker, $array)
	{
		if ( ! empty($array))
		{
			lostfilm::$sess_cookie = $array[1][0]."=".$array[2][0]." ".$array[1][1]."=".$array[2][1];
			$page = Sys::getUrlContent(
	        	array(
	        		'type'           => 'POST',
	        		'header'         => 0,
	        		'returntransfer' => 1,
	        		'url'            => 'http://lostfilm.tv/my.php',
	        		'cookie'         => lostfilm::$sess_cookie,
	        		'sendHeader'     => array('Host' => 'lostfilm.tv', 'Content-length' => strlen(lostfilm::$sess_cookie)),
	        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
	        	)
	        );
			preg_match('/<td align=\"left\">(.*)<br >/', $page, $out);
			if (isset($out[1]))
			{
    			lostfilm::$sess_cookie .= ' usess='.$out[1];
    			Database::setCookie($tracker, lostfilm::$sess_cookie);
    			Database::clearWarnings('lostfilm.tv');
            }
            else
            {
                //устанавливаем варнинг
				if (lostfilm::$warning == NULL)
    			{
    				lostfilm::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
				//останавливаем выполнение цепочки
				lostfilm::$exucution = FALSE;	                
            }
		}
		else 
		{
			//устанавливаем варнинг
			if (lostfilm::$warning == NULL)
   			{
   				lostfilm::$warning = TRUE;
   				Errors::setWarnings($tracker, 'credential_miss');
   			}
			//останавливаем выполнение цепочки
			lostfilm::$exucution = FALSE;		
		}
	}	
	
	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'header'         => 0,
        		'returntransfer' => 1,
        		'url'            => 'http://www.lostfilm.tv/',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'lostfilm.tv', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/ПРИВЕТ, <span class=\"wh\">.* <!-- (ID: .*) --><\/span>/U', $result))
			return TRUE;
		else
			return FALSE;		  
	}	
	
	//функция проверки введёного названия
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
		$data = preg_split('/\s/', $data);
		$time = $data[1];
		$data = $data[0];
		$data = preg_split('/\-/', $data);

		$month = Sys::dateNumToString($data[1]);
		$date = $data[2].' '.$month.' '.$data[0].' '.$time;
		
		return $date;
	}
	
	//функция учёта часового пояса
	private static function dateOffset($date)
	{
        $this_tz_str = date_default_timezone_get();
        $this_tz = new DateTimeZone($this_tz_str);
        $now = new DateTime("now", $this_tz);
        $offset = $this_tz->getOffset($now);

        return date('Y-m-d H:i:s', strtotime($date) + $offset);    	
	}
	
	//функция анализа эпизода
	private static function analysisEpisode($item)
	{
		preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
		if (isset($matches[0]))
		{
			$episode = $matches[0];
			$date = lostfilm::dateStringToNum($item->pubDate);
			return array('episode'=>$episode, 'date'=>lostfilm::dateOffset($date), 'link'=>(string)$item->link);
		}
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
		if (preg_match('/'.$name.'/i', $item->title))
		{
			if ($hd == 1)
			{
			    if (preg_match_all('/1080/', $item->title, $matches))
					return lostfilm::analysisEpisode($item);
				else
				{
    				if (preg_match_all('/720|HD/', $item->title, $matches))
    					return lostfilm::analysisEpisode($item);
                }
			}
			elseif ($hd == 2)
			{
				if (preg_match_all('/MP4/', $item->title, $matches))
					return lostfilm::analysisEpisode($item);
			}
			else
			{
				if (preg_match_all('/^(?!(.*720|.*HD|.*1080))/', $item->link, $matches))
					return lostfilm::analysisEpisode($item);
			}
		}
	}
	
	private static function getCookie($tracker)
	{
		//проверяем заполнены ли учётные данные
		if (Database::checkTrackersCredentialsExist($tracker))
		{
			//получаем учётные данные
			$credentials = Database::getCredentials($tracker);
			$login = iconv('utf-8', 'windows-1251', $credentials['login']);
			$password = $credentials['password'];
			
			$page = lostfilm::login('simple', $login, $password);
			if (preg_match_all('/Set-Cookie: (\w*)=(\S*)/', $page, $array))
			{
				lostfilm::$exucution = TRUE;
				lostfilm::getCookies($tracker, $array);
			}
			else
			{
				$page = lostfilm::login('hard', $login, $password);
				preg_match_all('/name=\"(.*)\"/iU', $page, $array_names);
				preg_match_all('/value=\"(.*)\"/iU', $page, $array_values);

				if ( ! empty($array_names) &&  ! empty($array_values))
				{
					$post = '';
					for($i=0; $i<count($array_values[1]); $i++)
						$post .= $array_names[1][$i+1].'='.$array_values[1][$i].'&';
				}
				$post = substr($post, 0, -1);

				$page = Sys::getUrlContent(
		        	array(
		        		'type'           => 'POST',
		        		'header'         => 1,
		        		'returntransfer' => 1,
		        		'url'            => 'http://www.lostfilm.tv/blg.php?ref=aHR0cDovL3d3dy5sb3N0ZmlsbS50di8=',
		        		'postfields'     => $post,
		        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
		        	)
		        );

				if (preg_match_all('/Set-Cookie: (\w*)=(\S*)/', $page, $array))
				{
					lostfilm::getCookies($tracker, $array);
					lostfilm::$exucution = TRUE;
				}	
			}			
		}
		else
		{
			//устанавливаем варнинг
			if (lostfilm::$warning == NULL)
			{
				lostfilm::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем выполнение цепочки
			lostfilm::$exucution = FALSE;						
		}	    	
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp, $hash)
	{
		//проверяем небыло ли до этого уже ошибок
		if (empty(lostfilm::$exucution) || (lostfilm::$exucution))
		{
			//проверяем получена ли уже кука
			if (empty(lostfilm::$sess_cookie))
			{
        		$cookie = Database::getCookie($tracker);
        		if (lostfilm::checkCookie($cookie))
        		{
        			lostfilm::$sess_cookie = $cookie;
        			//запускам процесс выполнения
        			lostfilm::$exucution = TRUE;
        		}			
        		else
            		lostfilm::getCookie($tracker);
			}

			lostfilm::$sess_cookie = Database::getCookie($tracker);
			lostfilm::$exucution = TRUE;

			//проверяем получена ли уже RSS лента
			if ( ! lostfilm::$log_page)
			{
				if (lostfilm::$exucution)
				{
					//получаем страницу
			        $page = Sys::getUrlContent(
			        	array(
			        		'type'           => 'GET',
			        		'returntransfer' => 1,
			        		'url'            => 'http://www.lostfilm.tv/rssdd.xml',
			        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
			        	)
			        );
			        
					lostfilm::$page = str_replace('<?xml version="1.0" encoding="windows-1251" ?>','<?xml version="1.0" encoding="utf-8"?>', $page);
					if ( ! empty(lostfilm::$page))
					{
						//читаем xml
						lostfilm::$xml_page = @simplexml_load_string(lostfilm::$page);
						//если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
						if ( ! lostfilm::$xml_page)
						{
							//устанавливаем варнинг
            				if (lostfilm::$warning == NULL)
                			{
                				lostfilm::$warning = TRUE;
                				Errors::setWarnings($tracker, 'rss_parse_false');
                			}
							//останавливаем выполнение цепочки
							lostfilm::$exucution = FALSE;
						}
						else
							lostfilm::$log_page = TRUE;
					}
					else
					{
						//устанавливаем варнинг
						if (lostfilm::$warning == NULL)
            			{
            				lostfilm::$warning = TRUE;
            				Errors::setWarnings($tracker, 'not_available');
            			}
						//останавливаем выполнение цепочки
						lostfilm::$exucution = FALSE;							
					}
				}
			}

			//если выполнение цепочки не остановлено
			if (lostfilm::$exucution)
			{
				if ( ! empty(lostfilm::$xml_page))
				{
					//сбрасываем варнинг
					Database::clearWarnings($tracker);
					$nodes = array();
					foreach (lostfilm::$xml_page->channel->item AS $item)
					{
					    array_unshift($nodes, $item);
					}
					
					foreach ($nodes as $item)
					{
						$serial = lostfilm::analysis($name, $hd, $item);
						if ( ! empty($serial))
						{
							$episode = substr($serial['episode'], 4, 2);
							$season = substr($serial['episode'], 1, 2);
							$date_str = lostfilm::dateNumToString($serial['date']);
						
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
								if ($hd == 1 || $hd == 3)
									$amp = 'HD';
								elseif ($hd == 2)
									$amp = 'MP4';
								else
									$amp = NULL;
								//сохраняем торрент в файл
                                $torrent = Sys::getUrlContent(
						        	array(
						        		'type'           => 'POST',
						        		'returntransfer' => 1,
						        		'url'            => $serial['link'],
						        		'cookie'         => lostfilm::$sess_cookie,
						        		'sendHeader'     => array('Host' => 'lostfilm.tv', 'Content-length' => strlen(lostfilm::$sess_cookie)),
						        	)
                                );
                                
                                if (Sys::checkTorrentFile($torrent))
                                {							
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
                                else
                                    Errors::setWarnings($tracker, 'save_file_fail');
							}
						}
					}
				}
			}
		}
	}
}
?>
