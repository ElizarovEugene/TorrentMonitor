<?php
class lostfilm
{
	protected static $sess_cookie;
	protected static $exucution = TRUE;
	protected static $warning;
	
	protected static $page;	
	protected static $log_page = FALSE;
	protected static $xml_page;

	//инициализируем класс
	public static function getInstance()
    {
        if ( ! isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
    
	//получаем куки для доступа к сайту
	private static function login($type, $login, $password)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0");
		curl_setopt($ch, CURLOPT_HEADER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($type == 'simple')
		{
			curl_setopt($ch, CURLOPT_URL, "http://lostfilm.tv/useri.php");
			curl_setopt($ch, CURLOPT_POSTFIELDS, "FormLogin={$login}&FormPassword={$password}&module=1&repage=user&act=login");
		}
		if ($type == 'hard')
		{
			curl_setopt($ch, CURLOPT_URL, "http://login.bogi.ru/login.php?referer=http%3A%2F%2Fwww.lostfilm.tv%2F");
			curl_setopt($ch, CURLOPT_POSTFIELDS, "login={$login}&password={$password}&module=1&target=http%3A%2F%2Flostfilm.tv%2F&repage=user&act=login");
		}
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}
	
	//получаем куки для доступа к сайту
	private static function loginBogi($post)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0");
		curl_setopt($ch, CURLOPT_HEADER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://www.lostfilm.tv/blg.php?ref=aHR0cDovL3d3dy5sb3N0ZmlsbS50di8=");
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{$post}");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}	

	
	//получаем страницу для парсинга
	private static function getPage($sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://lostfilm.tv/my.php");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: lostfilm.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
			
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	//получаем куки для доступа к сайту
	private static function getCookies($tracker, $array)
	{
		if ( ! empty($array))
		{
			lostfilm::$sess_cookie = $array[1][0]."=".$array[2][0]." ".$array[1][1]."=".$array[2][1];
			$page = lostfilm::getPage(lostfilm::$sess_cookie);
			preg_match("/<td align=\"left\">(.*)<br >/", $page, $out);
			lostfilm::$sess_cookie .= " usess=".$out[1];
			Database::setCookie($tracker, lostfilm::$sess_cookie);
			Database::clearWarnings('lostfilm.tv');
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
	
	//получаем страницу для парсинга
	private static function getContent()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://lostfilm.tv/rssdd.xml");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);		
		return $result;
	}
	
	//получаем содержимое torrent файла
	private static function getTorrent($link, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1");
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "{$link}");
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		$header[] = "Host: lostfilm.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://www.lostfilm.tv/");
		$header[] = "Host: lostfilm.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		if (preg_match('/ПРИВЕТ, <span class=\"wh\">.* <!-- (ID: \d*) --><\/span>/U', $result))
			return TRUE;
		else
			return FALSE;		  
	}	
	
	//функция проверки введёного названия
	public static function checkRule($data)
	{
		if (preg_match("/^[\.\+\s\'\`\:\;\-a-zA-Z0-9]+$/", $data))
			return TRUE;
		else
			return FALSE;
	}

	//функция преобразования даты из строки
	private static function dateStringToNum($data)
	{
		$data = substr($data, 5);
		$data = substr($data, 0, -6);
		
		$monthes = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
		$month = substr($data, 3, 3);
		$data = preg_replace("/(\d\d)-(\d\d)-(\d\d)/", "$3-$2-$1", str_replace($month, str_pad(array_search($month, $monthes)+1, 2, 0, STR_PAD_LEFT), $data));
		
		$data = preg_split("/\s/", $data);
		$date = $data[2].'-'.$data[1].'-'.$data[0].' '.$data[3];
		return $date;
	}
	
	//функция преобразования даты в строку
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -3);
		$data = preg_split("/\s/", $data);
		$time = $data[1];
		$data = $data[0];
		$data = preg_split("/\-/", $data);

		$monthes_num = array("/01/", "/02/", "/03/", "/04/", "/05/", "/06/", "/07/", "/08/", "/09/", "/10/", "/11/", "/12/");
		$monthes_ru = array("Янв", "Фев", "Мар", "Апр", "Мая", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек");
		$month = preg_replace($monthes_num, $monthes_ru, $data[1]);
		$date = $data[2].' '.$month.' '.$data[0].' '.$time;
		return $date;
	}
	
	//функция анализа эпизода
	private static function analysisEpisode($item)
	{
		preg_match('/\w\d{2}\.?\w\d{2}/', $item->link, $matches);
		if (isset($matches[0]))
		{
			$episode = $matches[0];
			$date = lostfilm::dateStringToNum($item->pubDate);
			return array('episode'=>$episode, 'date'=>$date, 'link'=>(string)$item->link);
		}
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
		if (preg_match('/\('.$name.'\)/i', $item->title))
		{
			if ($hd == 1)
			{
				if (preg_match_all('/720|HD/', $item->title, $matches))
					return lostfilm::analysisEpisode($item);
			}
			elseif ($hd == 2)
			{
				if (preg_match_all('/MP4/', $item->title, $matches))
					return lostfilm::analysisEpisode($item);
			}
			else
			{
				if (preg_match_all('/^(?!(.*720|.*HD))/', $item->link, $matches))
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
			$login = iconv("utf-8", "windows-1251", $credentials['login']);
			$password = $credentials['password'];
			
			$page = lostfilm::login('simple', $login, $password);
			if (preg_match_all("/Set-Cookie: (\w*)=(\S*)/", $page, $array))
			{
				lostfilm::getCookies($tracker, $array);
				lostfilm::$exucution = TRUE;
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
				$page = lostfilm::loginBogi($post);
				
				if (preg_match_all("/Set-Cookie: (\w*)=(\S*)/", $page, $array))
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
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp)
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
	
			//проверяем получена ли уже RSS лента
			if ( ! lostfilm::$log_page)
			{
				if (lostfilm::$exucution)
				{
					//получаем страницу
					lostfilm::$page = lostfilm::getContent();
					lostfilm::$page = str_replace('<?xml version="1.0" encoding="windows-1251" ?>','<?xml version="1.0" encoding="utf-8"?>', lostfilm::$page);
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
								$torrent = lostfilm::getTorrent($serial['link'], lostfilm::$sess_cookie);
								if ($hd == 1)
									$amp = 'HD';
								elseif ($hd == 2)
									$amp = 'MP4';
								else
									$amp = NULL;
								$file = '[lostfilm.tv]_'.$name.'_'.$serial['episode'].'_'.$amp.'.torrent';
								//сохраняем торрент в файл
								$client = ClientAdapterFactory::getStorage('file');
								$client->store($torrent, $id, $tracker, $name, $id, $timestamp, array('filename' => $file));
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $serial['date']);
								//обновляем сведения о последнем эпизоде
								Database::setNewEpisode($id, $serial['episode']);
								$episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
								$season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
								//отправляем уведомлении о новом торренте
								$message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
								Notification::sendNotification('notification', lostfilm::dateNumToString($serial['date']), $tracker, $message);
							}
						}
					}
				}
			}
		}
	}
}
?>
