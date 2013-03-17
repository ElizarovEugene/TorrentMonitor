<?php
class rutor
{
	protected static $sess_cookie;
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
	private static function getContent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://www.rutor.org/torrent/{$threme}");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$header[] = "Host: rutor.org\r\n";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	//получаем содержимое torrent файла
	public static function getTorrent($threme, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://d.rutor.org/download/{$threme}");
		curl_setopt($ch, CURLOPT_REFERER, "http://www.rutor.org/torrent/{$threme}");
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
		$date = $data;
		$date = date("Y-m-d H:i:s", strtotime($date));

		return $date;
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
		$data = str_replace('-', ' ', $data);
		$arr = preg_split("/\s/", $data);
		$date = $arr[0].' '.$arr[1].' '.$arr[2].' '.$arr[3];

		return $date;
	}

	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp)
	{
		rutor::$exucution = TRUE;

		if (rutor::$exucution)
		{
			//получаем страницу для парсинга
			$page = rutor::getContent($torrent_id, rutor::$sess_cookie);
			
			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match("/<tr><td class=\"header\">Добавлен<\/td><td>(.+)  \((.+) назад\)<\/td><\/tr>/", $page, $array))
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
							$date_str = $array[1];
							//если даты не совпадают, перекачиваем торрент
							if ($date != $timestamp)
							{
								//сохраняем торрент в файл
								$torrent = rutor::getTorrent($torrent_id, rutor::$sess_cookie);
								$client = ClientAdapterFactory::getStorage('file');
								$client->store($torrent, $id, $tracker, $name, $torrent_id, $timestamp);
								//обновляем время регистрации торрента в базе
								Database::setNewDate($id, $date);
								//отправляем уведомлении о новом торренте
								$message = $name.' обновлён.';
								Notification::sendNotification('notification', rutor::dateNumToString($date_str), $tracker, $message);
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