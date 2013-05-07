<?php
class tfile
{
	protected static $exucution;
	protected static $warning;

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

	//получаем страницу для парсинга
	public static function getContent($threme)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://tfile.me/forum/viewtopic.php?t={$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: tfile.me\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	//получаем содержимое torrent файла
	public static function getTorrent($threme)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://tfile.me/forum/download.php?id={$threme}&uk=1111111111");
		curl_setopt($ch, CURLOPT_REFERER, "http://tfile.me/forum/viewtopic.php?t={$threme}");
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

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
        $data = preg_replace("/(\d\d\d\d)-(\d\d)-(\d\d)/", "$3-$2-$1", $data);
        $data = explode('-', $data);
        switch ($data[1])
        {
            case 01: $m="Янв"; break;
            case 02: $m="Фев"; break;
            case 03: $m="Мар"; break;
            case 04: $m="Апр"; break;
            case 05: $m="Мая"; break;
            case 06: $m="Июн"; break;
            case 07: $m="Июл"; break;
            case 08: $m="Авг"; break;
            case 09: $m="Сен"; break;
            case 10: $m="Окт"; break;
            case 11: $m="Ноя"; break;
            case 12: $m="Дек"; break;
        }
        $dateTime = $data[0].' '.$m.' '.$data[2];
		return $dateTime;
	}
	
	//функция нахождения id для скачивания
	public static function findId($page)
	{
		if (preg_match("/download\.php\?id=(\d+)&uk=1111111111/", $page, $arrayId))
			return $arrayId[1];
		else
			return FALSE;
	}

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		tfile::$exucution = TRUE;

		if (tfile::$exucution)
		{
			//получаем страницу для парсинга
			$page = tfile::getContent($torrent_id);
			
			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/class=\"regDate\">(.+)<\/span>/", $page, $array))
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
							$date = tfile::dateStringToNum($array[1]);
							$date_str = tfile::dateNumToString($array[1]);
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//ищем на странице id торрента
								$torrent_id = tfile::findId($page);
								if (is_string($torrent_id))
								{
									if (Database::getSetting('download'))
									{
										//сохраняем торрент в файл
										$torrent = tfile::getTorrent($torrent_id);
										$client = ClientAdapterFactory::getStorage('file');
										$client->store($torrent, $id, $tracker, $name, $torrent_id, $timestamp);
									}

									//обновляем время регистрации торрента в базе
									Database::setNewDate($id, $date);
									//отправляем уведомлении о новом торренте
									$message = $name.' обновлён.';
									Notification::sendNotification('notification', $date_str, $tracker, $message);
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
