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

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		rutor::$exucution = TRUE;

		if (rutor::$exucution)
		{
			//получаем страницу для парсинга
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'GET',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://alt.rutor.org/torrent/'.$torrent_id.'/'
            	)
            );

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<tr><td class=\"header\">Добавлен<\/td><td>(.+)  \((.+) назад\)<\/td><\/tr>/', $page, $array))
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
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
								$torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'GET',
                                		'returntransfer' => 0,
                                		'url'            => 'http://d.rutor.org/download/'.$torrent_id.'/',
                                	)
                                );

								if ($auto_update)
								{
								    $name = Sys::getHeader('http://alt.rutor.org/torrent/'.$torrent_id.'/');
								    //обновляем заголовок торрента в базе
                                    Database::setNewName($id, $name);
								}

								$message = $name.' обновлён.';
								$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
							}
						}
						else
						{
							//устанавливаем варнинг
							if (rutor::$warning == NULL)
							{
								rutor::$warning = TRUE;
								Errors::setWarnings($tracker, 'not_available');
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
							Errors::setWarnings($tracker, 'not_available');
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
						Errors::setWarnings($tracker, 'not_available');
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
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				rutor::$exucution = FALSE;
			}
		}
	}
}
?>