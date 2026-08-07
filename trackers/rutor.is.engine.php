<?php
class rutor
{
	protected static $exucution;
	protected static $warning;

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
		$date = $data;
		$date = date('Y-m-d H:i:s', strtotime($date));

		return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', $data);
		$arr = preg_split("/\s/", $data);
		$date = $arr[0].' '.Sys::dateNumToString($arr[1]).' '.$arr[2].' '.$arr[3];

		return $date;
	}

	//формируем параметры "проверочного" запроса для curl_multi
	public static function getRequestParams($params)
	{
		extract($params);
		rutor::$exucution = TRUE;

		$url = 'https://rutor.is/torrent/'.$torrent_id.'/';

		return array(
			'url'     => $url,
			'options' => array(
				CURLOPT_HTTPGET       => 1,
				CURLOPT_FOLLOWLOCATION => 1,
			) + Sys::getProxyOptions($url),
		);
	}

	//разбираем полученную страницу, возвращаем изменения для batchUpdateTorrents или null
	public static function parse($params, $page)
	{
		extract($params);
		$return = NULL;

		if ( ! empty($page))
		{
			//ищем на странице дату регистрации торрента
			if (preg_match('/<td class=\"header\">Добавлен<\/td><td>(.+) \((.+) назад\)<\/td>/', $page, $array))
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
						$date = rutor::dateStringToNum($array[1]);
						$date_str = rutor::dateNumToString($array[1]);
						$return = array($id => array('error' => 0));
						//если даты не совпадают, перекачиваем торрент
						if ($date != $timestamp)
						{
							//сохраняем торрент в файл
							$torrent = Sys::getUrlContent(
                            	array(
                            		'type'           => 'GET',
                            		'follow'         => 1,
                            		'returntransfer' => 0,
                            		'url'            => 'https://d.rutor.info/download/'.$torrent_id,
                            	)
                            );

                            if (Sys::checkTorrentFile($torrent))
                            {
								if ($auto_update)
								{
								    $name = Sys::parseHeader($tracker, $page);
								}

								$message = $name.' обновлён.';
								$saved = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str, $name);

								if ($saved)
								{
									if ($auto_update)
										//обновляем заголовок торрента в базе
										$return[$id]['name'] = $name;
									//обновляем время регистрации торрента в базе
									$return[$id]['timestamp'] = $date;
									//сбрасываем варнинг
									Database::clearWarnings($tracker);
								}
								else
									Errors::setWarnings($tracker, 'save_file_fail', $id);
                            }
                            else
                                Errors::setWarnings($tracker, 'torrent_file_fail', $id);
						}
					}
					else
					{
						//устанавливаем варнинг
						if (rutor::$warning == NULL)
						{
							rutor::$warning = TRUE;
							Errors::setWarnings($tracker, 'cant_find_date', $id);
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						rutor::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (rutor::$warning == NULL)
					{
						rutor::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_date', $id);
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					rutor::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (rutor::$warning == NULL)
				{
					rutor::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_find_date', $id);
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutor::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (rutor::$warning == NULL)
			{
				rutor::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			rutor::$exucution = FALSE;
		}

		rutor::$warning = NULL;
		return $return;
	}
}
?>
