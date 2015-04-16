<?php
class lostfilmmirror
{
	protected static $exucution;
	protected static $warning;
	
	protected static $page;	
	protected static $log_page;
	protected static $xml_page;
	
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
	
	//функция анализа эпизода
	private static function analysisEpisode($item)
	{
		preg_match('/s\d{2}\.?e\d{2}/i', $item->link, $matches);
		if (isset($matches[0]))
		{
			$episode = $matches[0];
			return array('episode'=>$episode, 'date'=>(string)$item->pubDate, 'link'=>(string)$item->link);
		}
	}
	
	//функция анализа xml ленты
	private static function analysis($name, $hd, $item)
	{
        $name = str_replace(' ', '.', $name);
		if (preg_match('/'.$name.'/i', $item->title))
		{
            if ($hd == 0)
            {
                if (preg_match_all('/avi|AVI/', $item->link, $matches))
                    return lostfilmmirror::analysisEpisode($item);
            }
            elseif ($hd == 1)
            {
                if (preg_match_all('/mkv|MKV/', $item->link, $matches))
                    return lostfilmmirror::analysisEpisode($item);
            }
            elseif ($hd == 2)
            {
                if (preg_match_all('/mp4|MP4/', $item->link, $matches))
                    return lostfilmmirror::analysisEpisode($item);
            }
		}
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $hd, $ep, $timestamp, $hash)
	{
		//проверяем получена ли уже RSS лента
		if ( ! lostfilmmirror::$log_page)
		{
			//получаем страницу
	        lostfilmmirror::$page = Sys::getUrlContent(
	        	array(
	        		'type'           => 'GET',
	        		'returntransfer' => 1,
	        		'url'            => 'http://korphome.ru/lostfilm.tv/rss.xml',
	        	)
	        );

			if ( ! empty(lostfilmmirror::$page))
			{
				//читаем xml
				lostfilmmirror::$xml_page = @simplexml_load_string(lostfilmmirror::$page);

				//если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
				if ( ! lostfilmmirror::$xml_page)
				{
					//устанавливаем варнинг
    				if (lostfilmmirror::$warning == NULL)
        			{
        				lostfilmmirror::$warning = TRUE;
        				Errors::setWarnings($tracker, 'rss_parse_false');
        			}
					//останавливаем выполнение цепочки
					lostfilmmirror::$exucution = FALSE;
				}
				else
				{
					lostfilmmirror::$log_page = TRUE;
					lostfilmmirror::$exucution = TRUE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (lostfilmmirror::$warning == NULL)
    			{
    				lostfilmmirror::$warning = TRUE;
    				Errors::setWarnings($tracker, 'not_available');
    			}
				//останавливаем выполнение цепочки
				lostfilmmirror::$exucution = FALSE;							
			}
		}

		//если выполнение цепочки не остановлено
		if (lostfilmmirror::$exucution)
		{
			if ( ! empty(lostfilmmirror::$xml_page))
			{
				//сбрасываем варнинг
				Database::clearWarnings($tracker);
				$nodes = array();
				foreach (lostfilmmirror::$xml_page->channel->item AS $item)
				{
				    array_unshift($nodes, $item);
				}
				
				foreach ($nodes as $item)
				{
					$serial = lostfilmmirror::analysis($name, $hd, $item);
					if ( ! empty($serial))
					{
						$episode = substr($serial['episode'], 4, 2);
						$season = substr($serial['episode'], 1, 2);
						$date_str = lostfilmmirror::dateNumToString($serial['date']);
					
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
								$amp = 'SD';
							//сохраняем торрент в файл
                            $torrent = Sys::getUrlContent(
					        	array(
					        		'type'           => 'GET',
					        		'returntransfer' => 1,
					        		'url'            => $serial['link'],
					        	)
                            );
                            
                            if (Sys::checkTorrentFile($torrent))
                            {							
								$file = str_replace(' ', '.', $name).'.S'.$season.'E'.$episode.'.'.$amp;
								$episode = (substr($episode, 0, 1) == 0) ? substr($episode, 1, 1) : $episode;
								$season = (substr($season, 0, 1) == 0) ? substr($season, 1, 1) : $season;
								$message = $name.' '.$amp.' обновлён до '.$episode.' серии, '.$season.' сезона.';
								$status = Sys::saveTorrent($tracker, $file, $torrent, $id, $hash, $message, $date_str);

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
?>