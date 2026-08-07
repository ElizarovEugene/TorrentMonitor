<?php
class lostfilmmirror
{
	protected static $exucution;
	protected static $warning;

	//резервный адрес ленты, используется, если основной источник недоступен
	const MIRROR_URL = 'https://lf.tormon.ru/rss.xml';

	//функция преобразования даты в строку
	private static function dateNumToString($data)
	{
		$data = substr($data, 0, -6);
		$data = preg_split('/\s/', $data);
		$time = $data[4];

		$date = $data[1].' '.$data[2].' '.$data[3].' '.$time;
		return $date;
	}

	//функция преобразования даты в строку
	private static function dateStringToNum($data)
	{
		$data = substr($data, 0, -6);
		$data = preg_split('/\s/', $data);
		$time = $data[4];

		$month = Sys::dateStringToNum($data[2]);

		$date = $data[3].'-'.$month.'-'.$data[1].' '.$time;
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

	//формируем параметры "проверочного" запроса для curl_multi
	public static function getRequestParams($params)
	{
		extract($params);
		lostfilmmirror::$exucution = TRUE;

		$url = 'https://rss.bzda.ru/rss.xml';

		return array(
			'url'     => $url,
			'options' => array(
				CURLOPT_HTTPGET       => 1,
				CURLOPT_RETURNTRANSFER => 1,
			) + Sys::getProxyOptions($url),
		);
	}

	//разбираем полученную страницу, возвращаем изменения для batchUpdateTorrents или null
	public static function parse($params, $page)
	{
		extract($params);
		$return = NULL;

		if (empty($page))
		{
			//основной источник недоступен - пробуем зеркало
			$mirrorUrl = lostfilmmirror::MIRROR_URL;
			$page = Sys::getUrlContent(array(
				'type'           => 'GET',
				'returntransfer' => 1,
				'url'            => $mirrorUrl,
			));
		}

		if ( ! empty($page))
		{
			//читаем xml
			$xml_page = @simplexml_load_string($page);

			//если XML пришёл с ошибками - останавливаем выполнение, иначе - ставим флажок, что получаем страницу
			if ( ! $xml_page)
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
				lostfilmmirror::$exucution = TRUE;

				//сбрасываем варнинг
				Database::clearWarnings($tracker);
				$nodes = array();
				foreach ($xml_page->channel->item AS $item)
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
								$saved = Sys::saveTorrent($tracker, $file, $torrent, $id, $hash, $message, $date_str, $name);

								if ($saved)
								{
									//обновляем время регистрации торрента в базе
									if ($return === NULL)
										$return = array();
									if ( ! isset($return[$id]))
										$return[$id] = array();
									$return[$id]['timestamp'] = lostfilmmirror::dateStringToNum($serial['date']);
									//обновляем сведения о последнем эпизоде
									$return[$id]['ep'] = $serial['episode'];
								}
								else
									Errors::setWarnings($tracker, 'save_file_fail', $id);
                            }
                            else
                                Errors::setWarnings($tracker, 'torrent_file_fail', $id);
						}
					}
				}
			}
		}
		else
		{
			//устанавливаем варнинг
			if (lostfilmmirror::$warning == NULL)
			{
				lostfilmmirror::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_find_rss');
			}
			//останавливаем выполнение цепочки
			lostfilmmirror::$exucution = FALSE;
		}

		return $return;
	}
}
?>