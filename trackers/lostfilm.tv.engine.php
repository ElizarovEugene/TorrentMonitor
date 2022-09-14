<?php
class lostfilm
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
				'url'            => 'https://lostfilm.tv',
				'cookie'         => $sess_cookie,
				'sendHeader'     => array('Host' => 'lostfilm.tv', 'Content-length' => strlen($sess_cookie)),
			)
		);

		if (preg_match('/<a href=\"\/my\" title=\"Перейти в личный кабинет\">/', $result))
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
	private static function analysis($name, $item)
	{
		if (preg_match('/'.$name.'/i', $item->title))
		{
    		preg_match('/S\d{2}E\d{2}/', $item->title, $matches);
    		if (isset($matches[0]))
                $episode = $matches[0];

    		preg_match('/<img src=\"\/\/static\.lostfilm\.(tv|win|top|site)\/Images\/(.*)\/Posters\/image\.jpg\" alt=\"\" \/>/', $item->description, $matches);
    		if (isset($matches[2]))
    			$id = $matches[2];
    		
    			
            $date = lostfilm::dateStringToNum($item->pubDate);
            $dateTimeUser = new DateTimeZone(date_default_timezone_get());
            $dateTimeUser = new DateTime('now', $dateTimeUser);
            $offset = $dateTimeUser->getOffset() / 60 /60 ;
            $dateTimeZoneUTC = new DateTimeZone('UTC');
            $dateTimeUTC = new DateTime($date, $dateTimeZoneUTC);
            $dateTimeUTC->add(new DateInterval('PT'.$offset.'H'));
            $date = $dateTimeUTC->format('Y-m-d H:i:s');
            
    		return array('ID'=>$id, 'episode'=>$episode, 'date'=>$date);
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
            		'url'            => 'https://lostfilm.tv/ajaxik.php',
            		'postfields'     => 'act=users&type=login&mail='.$login.'&pass='.$password.'&rem=1',
            	)
            );

			if ( ! empty($page))
			{
				if (preg_match_all('/\"need_captcha\":true/', $page, $array))
    			{
					//устанавливаем варнинг
					if (lostfilm::$warning == NULL)
        			{
        				lostfilm::$warning = TRUE;
        				Errors::setWarnings($tracker, 'captcha');
        			}
					//останавливаем выполнение цепочки
					lostfilm::$exucution = FALSE;        			
                }
				elseif (preg_match_all('/\"error\"/', $page, $array))
    			{
					//устанавливаем варнинг
					if (lostfilm::$warning == NULL)
        			{
        				lostfilm::$warning = TRUE;
        				Errors::setWarnings($tracker, 'credential_wrong');
        			}
					//останавливаем выполнение цепочки
					lostfilm::$exucution = FALSE;        			
                }
				//проверяем подходят ли учётные данные
				elseif (preg_match_all('/Set-Cookie: lf_session=(.*);/Ui', $page, $array))
				{
    				$num = count ($array[1]);
    				lostfilm::$sess_cookie = 'lf_session='.$array[1][$num-1];
					Database::setCookie($tracker, lostfilm::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					lostfilm::$exucution = TRUE;
				}
				//если не удалось получить никаких данных со страницы, значит трекер не доступен
				else
				{
					//устанавливаем варнинг
					if (lostfilm::$warning == NULL)
        			{
        				lostfilm::$warning = TRUE;
        				Errors::setWarnings($tracker, 'cant_find_cookie');
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
    				Errors::setWarnings($tracker, 'cant_get_auth_page');
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
	
	//основная функция
	public static function main($params)
	{
    	extract($params);
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
			
			//проверяем получена ли уже RSS лента
			if ( ! lostfilm::$log_page)
			{
				if (lostfilm::$exucution)
				{
					//получаем страницу
			        lostfilm::$page = Sys::getUrlContent(
			        	array(
			        		'type'           => 'GET',
			        		'returntransfer' => 1,
			        		'url'            => 'https://lostfilm.tv/rss.xml',
			        	)
			        );

					if ( ! empty(lostfilm::$page))
					{
    					lostfilm::$page = preg_replace('/\&/', '&amp;', lostfilm::$page);
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
            				Errors::setWarnings($tracker, 'cant_find_rss');
            			}
						//останавливаем выполнение цепочки
						lostfilm::$exucution = FALSE;
					}
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
					$serial = lostfilm::analysis($name, $item);
					if ( ! empty($serial))
					{
						$episode = substr($serial['episode'], 4, 2);
						$season = substr($serial['episode'], 1, 2);
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
    						if (substr($season, 0, 1) == 0)
    						    $season = substr($season, 1);
							//получаем страницу с ссылкой на страницу с
							$page = Sys::getUrlContent(
								array(
									'type'           => 'GET',
									'returntransfer' => 1,
									'url'            => 'https://lostfilm.tv/v_search.php?c='.$serial['ID'].'&s='.$season.'&e='.$episode.'&'.lostfilm::$sess_cookie,
									'sendHeader'     => array('Host' => 'lostfilm.tv'),
								)
							);
							
							if (preg_match('/location\.replace\(\"(.*)\"\);/', $page, $matches))
							{
                                //получаем страницу с ссылками на torrent-файлы
                                $page = Sys::getUrlContent(
                                    array(
                                        'type'           => 'GET',
                                        'returntransfer' => 1,
                                        'url'            => $matches[1],
                                        'sendHeader'     => array('Host' => 'retre.org'),
                                    )
                                );

                                if ($hd == 0)
                                {
                                    $str = 'SD';
                                    $quality = '(WEBRip|WEB-DLRip|HDTVRip)';
                                    $amp = 'SD';
                                }
                                if ($hd == 1)
                                {
                                    $str = '1080';
                                    $quality = '1080p? (WEBRip|WEB-DLRip|HDTVRip)';
                                    $amp = 'HD';
                                }
                                if ($hd == 2)
                                {
                                    $str = 'MP4';
                                    $quality = '720p? (WEB-DLRip|WEBRip|WEB-DL|HDTVRip)';
                                    $amp = 'MP4';
                                }

                                if (preg_match_all('/<div class=\"inner-box--label\">\n'.$str.'(\t\t\t|\s{12})<\/div>\n\s*<div class=\"inner-box--link main\"><a href=\"(https:\/\/(n.)?tracktor\.(in|site)\/td\.php\?s=.*)\">[\s\S]*'.$quality.'<\/a><\/div>/U', $page, $matches))
                                {
        							$torrent = Sys::getUrlContent(
    						        	array(
    						        		'type'           => 'GET',
    						        		'returntransfer' => 1,
    						        		'url'            => $matches[2][0],
    						        		'sendHeader'     => array('Host' => 'tracktor.in'),
    						        	)
                                    );

                                    if (Sys::checkTorrentFile($torrent))
                                    {	
        								$file = str_replace(' ', '.', $name).'.S'.$season.'E'.$episode.'.'.$amp;
        								$episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
        								$season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
        								$message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
        								$status = Sys::saveTorrent($tracker, $file, $torrent, $id, $hash, $message, lostfilm::dateNumToString($serial['date']), $name);
    
        								//обновляем время регистрации торрента в базе
        								Database::setNewDate($id, $serial['date']);
        								//обновляем сведения о последнем эпизоде
        								Database::setNewEpisode($id, $serial['episode']);
        								//очищаем ошибки
        								Database::setErrorToThreme($id, 0);
        								Database::setClosedThreme($id, 0);
                                    }
                                    else
                                    {
                                        Errors::setWarnings($tracker, 'torrent_file_fail', $id);
                                    }
                                }
							}
						}
					}
				}
			}
		}
	}
}
?>