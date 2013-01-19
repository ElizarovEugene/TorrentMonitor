<?php
class novafilm
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;
	
	protected static $page;	
	protected static $log_page;
	protected static $xml_page;

	//инициализируем класс
	public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
    
	//получаем куки для доступа к сайту
	private static function login($login, $password)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_HEADER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, 'http://novafilm.tv/auth/login');
		$boundary = "----WebKitFormBoundaryRQ3KHJbpfmPf11v6";
		$data = "--{$boundary}
Content-Disposition: form-data; name=\"return\"

/auth
--{$boundary}
Content-Disposition: form-data; name=\"username\"

{$login}
--{$boundary}
Content-Disposition: form-data; name=\"password\"

{$password}
--{$boundary}
Content-Disposition: form-data; name=\"login\"

Хочу войти!
--{$boundary}--";

		$header[] = "Content-Type: multipart/form-data; boundary=".$boundary;
		$header[] = "Content-Length: ".strlen($data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	//получаем страницу для парсинга
	private static function getContent()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//временное решение, пока новавцы не поднимут свою жопу, что бы починить собственную rss
		curl_setopt($ch, CURLOPT_URL, "http://www.ulitka.tv/novafilm.xml");
		#http://novafilm.tv/rss/rssd.xml
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	//получаем содержимое torrent файла
	private static function getTorrent($link, $sess_cookie, $where)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "{$link}");
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		$header[] = "Host: novafilm.tv\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);
		
		file_put_contents($where, $result);
	}
	
	public static function checkRule($data)
	{
		if (preg_match("/^[\.\+\s\'a-zA-Z0-9]+$/", $data))
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
		$data = str_replace('-', ' ', $data);
		$arr = preg_split("/\s/", $data);
		
		$monthes_en = array("/01/", "/02/", "/03/", "/04/", "/05/", "/06/", "/07/", "/08/", "/09/", "/10/", "/11/", "/12/");
		$monthes_ru = array("Янв", "Фев", "Мар", "Апр", "Мая", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек");
		
		$month = preg_replace($monthes_en, $monthes_ru, $arr[1]);
		$date = $arr[2].' '.$month.' '.$arr[0].' '.$arr[3];
		return $date;
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
		if (preg_match('/\b'.$name.'\b/i', $item->category))
		{
			if ($hd)
			{
				if (preg_match_all('/720/', $item->link, $matches))
				{
					preg_match('/\w\d{2}\.?\w\d{2}/', $item->link, $matches);
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
				if (preg_match_all('/^(?!(.*720))/', $item->link, $matches))
				{
					preg_match('/\w\d{2}\.?\w\d{2}/', $item->link, $matches);
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
	
	//основная функция
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp)
	{
		//проверяем небыло ли до этого уже ошибок
		if (empty(novafilm::$exucution) || (novafilm::$exucution))
		{
			//проверяем получена ли уже кука
			if (empty(novafilm::$sess_cookie))
			{
				//проверяем заполнены ли учётные данные
				if (Database::checkTrackersCredentialsExist($tracker))
				{
					//получаем учётные данные
					$credentials = Database::getCredentials($tracker);
					$login = iconv("utf-8", "windows-1251", $credentials['login']);
					$password = $credentials['password'];
					
					novafilm::$page = novafilm::login($login, $password);
				
					if ( ! empty(novafilm::$page))
					{
						//проверяем подходят ли учётные данные
						if (preg_match_all("/Set-Cookie: (\w*)=(\S*)/", novafilm::$page, $array))
						{
							novafilm::$sess_cookie = $array[1][2]."=".$array[2][2];
							//запускам процесс выполнения, т.к. не может работать без кук
							novafilm::$exucution = TRUE;
						}
						//проверяем нет ли сообщения о неправильном логине/пароле
						elseif (preg_match("/\/do\/recover/", novafilm::$page, $out))
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
			
			//проверяем получена ли уже RSS лента
			if( ! novafilm::$log_page)
			{
				if (novafilm::$exucution)
				{
					//получаем страницу
					novafilm::$page = novafilm::getContent();
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
							$path = Database::getSetting('path');
							$file = $path.'[novafilm.tv]_'.$name.'_'.$serial['episode'].'_'.$amp.'.torrent';
							novafilm::getTorrent($serial['link'], novafilm::$sess_cookie, $file);
							//обновляем время регистрации торрента в базе
							Database::setNewDate($id, $serial['date']);
							//обновляем сведения о последнем эпизоде
							Database::setNewEpisode($id, $serial['episode']);
							$episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
							$season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
							//отправляем уведомлении о новом торренте
							$message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
							Notification::sendNotification('notification', novafilm::dateNumToString($serial['date']), $tracker, $message);
						}
					}
				}
			}
		}
	}
}
?>