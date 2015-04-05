<?php
class tfile
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
	    $date = $data.':00';
	    return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = preg_replace('/(\d\d\d\d)-(\d\d)-(\d\d)/', '$3-$2-$1', $data);
		$data = explode(' ', $data);
		$time = $data[1];
		$data = explode('-', $data[0]);
		$m = Sys::dateNumToString($data[1]);
		$dateTime = $data[0].' '.$m.' '.$data[2].' '.$time;
		return $dateTime;
	}
	
	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		tfile::$exucution = TRUE;

		if (tfile::$exucution)
		{
			//получаем страницу для парсинга
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'GET',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://tfile.me/forum/viewtopic.php?t='.$torrent_id
            	)
            );
			
			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/class=\"regDate\">(.+)<\/span>/', $page, $array))
				{
					//проверяем удалось ли получить дату со страницы
					if (isset($array[1]))
					{
						//если дата не равна ничему
						if ( ! empty($array[1]))
						{
							//находим имя торрента для скачивания		
							if (preg_match('/download\.php\?id=(\d+)&uk=1111111111/', $page, $link))
							{
								//сбрасываем варнинг
								Database::clearWarnings($tracker);
								//приводим дату к общему виду
								$date = tfile::dateStringToNum($array[1]);
								$date_str = tfile::dateNumToString($array[1]);
								//если даты не совпадают, перекачиваем торрент
								if ($date != $timestamp)
								{
									//ищем на странице id торрента
									$download_id = $link[1];
									if (is_string($torrent_id))
									{
										//сохраняем торрент в файл
										$torrent = Sys::getUrlContent(
		                                	array(
		                                		'type'           => 'GET',
		                                		'returntransfer' => 1,
		                                		'url'            => 'http://tfile.me/forum/download.php?id='.$download_id.'&uk=1111111111',
		                                		'sendHeader'     => array('Host' => 'tfile.me'),
		                                		'referer'        => 'http://tfile.me/forum/viewtopic.php?t='.$torrent_id,
		                                	)
		                                );
										$message = $name.' обновлён.';
										$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
        								//обновляем время регистрации торрента в базе
										Database::setNewDate($id, $date);
										
										if ($auto_update)
        								{
        								    $name = Sys::getHeader('http://tfile.me/forum/viewtopic.php?t='.$torrent_id);
        								    //обновляем заголовок торрента в базе
                                            Database::setNewName($id, $name);
        								}
									}
									else
									{
										//устанавливаем варнинг
										if (tfile::$warning == NULL)
										{
											tfile::$warning = TRUE;
											Errors::setWarnings($tracker, 'not_available');
										}
										//останавливаем процесс выполнения, т.к. не может работать без кук
										tfile::$exucution = FALSE;
									}
								}
							}
						}
						else
						{
							//устанавливаем варнинг
							if (tfile::$warning == NULL)
							{
								tfile::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
							}
							//останавливаем процесс выполнения, т.к. не может работать без кук
							tfile::$exucution = FALSE;
						}
					}
					else
					{
						//устанавливаем варнинг
						if (tfile::$warning == NULL)
						{
							tfile::$warning = TRUE;
							Errors::setWarnings($tracker, 'not_available');
						}
						//останавливаем процесс выполнения, т.к. не может работать без кук
						tfile::$exucution = FALSE;
					}
				}
				else
				{
					//устанавливаем варнинг
					if (tfile::$warning == NULL)
					{
						tfile::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					tfile::$exucution = FALSE;
				}
			}
			else
			{
				//устанавливаем варнинг
				if (tfile::$warning == NULL)
				{
					tfile::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				tfile::$exucution = FALSE;
			}
		}
	}
}
?>