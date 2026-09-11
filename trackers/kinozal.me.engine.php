<?php
class kinozalme
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'returntransfer' => 1,
        		'url'            => 'https://kinozal.me',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'kinozal.me', 'Content-length' => strlen($sess_cookie)),
        		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
        	)
        );

		if (preg_match('/<a href=\'\/userdetails\.php\?id=\d*\'>.*<\/a>/U', $result))
			return TRUE;
		else
			return FALSE;
	}

	//функция проверки введёного URL`а
	public static function checkRule($data)
	{
		if (preg_match('/\D+/', $data))
			return FALSE;
		else
			return TRUE;
	}

	//функция преобразования даты (эпоха creation date из .torrent -> отображаемая строка)
	private static function dateNumToString($timestamp)
	{
		$day   = date('d', $timestamp);
		$month = Sys::dateNumToString(date('m', $timestamp));
		$year  = date('Y', $timestamp);
		$time  = date('H:i', $timestamp);

		return $day.' '.$month.' '.$year.' в '.$time;
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

			//авторизовываемся на трекере
			$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'https://kinozal.me/takelogin.php',
            		'postfields'     => 'username='.$login.'&password='.$password.'&returnto=',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );

			if ( ! empty($page))
			{
				//проверяем подходят ли учётные данные
				if (preg_match('/Не верно указан пароль/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'credential_wrong');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalme::$exucution = FALSE;
				}
				//проверяем нет ли блокировки
				if (preg_match('/Превышен лимит попыток входа в профиль <br>Попробуйте через 2 часа/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'limit');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalme::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.+);/iU', $page, $array))
				{
					kinozalme::$sess_cookie = $array[1][0].'; '.$array[1][1].';';
					Database::setCookie($tracker, kinozalme::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					kinozalme::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (kinozalme::$warning == NULL)
					{
						kinozalme::$warning = TRUE;
						Errors::setWarnings($tracker, 'cant_find_cookie');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozalme::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (kinozalme::$warning == NULL)
				{
					kinozalme::$warning = TRUE;
					Errors::setWarnings($tracker, 'cant_get_auth_page');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozalme::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozalme::$warning == NULL)
			{
				kinozalme::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozalme::$exucution = FALSE;
		}
	}

	//путь к файлу с размерами .torrent файлов (без изменения схемы БД)
	private static function sizesFilePath()
	{
		return dirname(__FILE__).'/../torrents/.sizes.json';
	}

	//читаем сохранённые размеры .torrent файлов всех kinozal-движков
	private static function readSizes()
	{
		$path = kinozalme::sizesFilePath();
		if ( ! file_exists($path))
			return array();

		$json = @file_get_contents($path);
		if ($json === FALSE)
			return array();

		$data = json_decode($json, TRUE);
		return is_array($data) ? $data : array();
	}

	//сохраняем размер .torrent файла темы (атомарно, tmp + rename)
	private static function writeSize($tracker, $torrent_id, $size)
	{
		$path = kinozalme::sizesFilePath();
		$data = kinozalme::readSizes();
		if ( ! isset($data[$tracker]))
			$data[$tracker] = array();
		$data[$tracker][$torrent_id] = (int) $size;

		//уникальный tmp-файл (несколько процессов ТМ могут писать параллельно) + LOCK_EX
		$tmp = $path.'.'.getmypid().'.tmp';
		if (file_put_contents($tmp, json_encode($data), LOCK_EX) !== FALSE)
			rename($tmp, $path);
	}

	//дата создания раздачи из bencode .torrent-файла (13:creation datei<epoch>e)
	private static function torrentCreationDate($torrent)
	{
		if (preg_match('/13:creation datei(\d+)e/', $torrent, $m))
			return (int) $m[1];

		return NULL;
	}

	//имя раздачи (info.name) из bencode .torrent-файла; ищем ТОЛЬКО после первого 4:infod
	private static function torrentInfoName($torrent)
	{
		$infoPos = strpos($torrent, '4:infod');
		if ($infoPos === FALSE)
			return NULL;

		if ( ! preg_match('/4:name(\d+):/', $torrent, $m, PREG_OFFSET_CAPTURE, $infoPos))
			return NULL;

		$len       = (int) $m[1][0];
		$nameStart = $m[0][1] + strlen($m[0][0]);
		$name      = substr($torrent, $nameStart, $len);

		return mb_check_encoding($name, 'UTF-8') ? $name : iconv('windows-1251', 'utf-8//IGNORE', $name);
	}

	//скачиваем и сохраняем обновлённый .torrent (вызывается только когда HEAD-проверка
	//в parse() определила, что раздача обновилась либо тема новая)
	public static function work($id, $tracker, $name, $torrent_id, $hash, &$return)
	{
		//сохраняем торрент в файл
        $torrent = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'returntransfer' => 1,
        		'url'            => 'https://kinozal.me/download.php?id='.$torrent_id,
        		'cookie'         => kinozalme::$sess_cookie,
        		'sendHeader'     => array('Host' => 'kinozal.me', 'Content-length' => strlen(kinozalme::$sess_cookie)),
        		'referer'        => 'https://kinozal.me/details.php?id='.$torrent_id,
        	)
        );

		if (preg_match('/<a href=\'\/pay_mode\.php\#tcounter\' class=sbab>/', $torrent))
		{
			//устанавливаем варнинг
			if (kinozalme::$warning == NULL)
			{
				kinozalme::$warning = TRUE;
				Errors::setWarnings($tracker, 'max_torrent');
			}
			//останавливаем процесс выполнения
			kinozalme::$exucution = FALSE;
		}
		else
		{
            if (Sys::checkTorrentFile($torrent))
            {
            	//дата создания раздачи - из bencode .torrent-файла, нет ключа - берём текущее время
            	$creationDate = kinozalme::torrentCreationDate($torrent);
            	$timestamp    = $creationDate !== NULL ? $creationDate : time();
            	$date         = date('Y-m-d H:i:s', $timestamp);
            	$date_str     = kinozalme::dateNumToString($timestamp);

				$message = $name.' обновлён.';
				$saved = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str, $name);

				if ($saved)
				{
				    //обновляем время регистрации торрента в базе
				    $return[$id]['timestamp'] = $date;
				    //сбрасываем варнинг
				    Database::clearWarnings($tracker);
				    $return[$id]['error'] = 0;
				    //запоминаем размер файла для последующего HEAD-сравнения
				    kinozalme::writeSize($tracker, $torrent_id, strlen($torrent));
				}
				else
				    Errors::setWarnings($tracker, 'save_file_fail', $id);
    		}
    		else
                Errors::setWarnings($tracker, 'torrent_file_fail', $id);
		}
	}

	//получаем имя темы из info.name .torrent-файла (details.php недоступен за CF).
	//используется при добавлении темы по URL (System::getHeader)
	public static function fetchName($torrent_id)
	{
		$tracker = 'kinozal.me';
		$cookie = Database::getCookie($tracker);
		if (kinozalme::checkCookie($cookie))
		{
			kinozalme::$sess_cookie = $cookie;
			//запускам процесс выполнения
			kinozalme::$exucution = TRUE;
		}
		else
			kinozalme::getCookie($tracker);

		if ( ! kinozalme::$exucution)
			return NULL;

        $torrent = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'returntransfer' => 1,
        		'url'            => 'https://kinozal.me/download.php?id='.$torrent_id,
        		'cookie'         => kinozalme::$sess_cookie,
        		'sendHeader'     => array('Host' => 'kinozal.me', 'Content-length' => strlen(kinozalme::$sess_cookie)),
        		'referer'        => 'https://kinozal.me/details.php?id='.$torrent_id,
        	)
        );

		if ( ! Sys::checkTorrentFile($torrent))
			return NULL;

		return kinozalme::torrentInfoName($torrent);
	}

	//формируем параметры "проверочного" запроса для curl_multi (резолв куки последовательный, как и раньше).
	//проверяем download.php вместо details.php (последний за интерактивным Cloudflare-челленджем)
	public static function getRequestParams($params)
	{
		extract($params);
		$cookie = Database::getCookie($tracker);
		if (kinozalme::checkCookie($cookie))
		{
			kinozalme::$sess_cookie = $cookie;
			//запускам процесс выполнения
			kinozalme::$exucution = TRUE;
		}
		else
    		kinozalme::getCookie($tracker);

		if ( ! kinozalme::$exucution)
		{
			kinozalme::$warning = NULL;
			return array('url' => NULL);
		}

		$url = 'https://kinozal.me/download.php?id='.$torrent_id;

		//HEAD-запрос: интересует только код ответа и заголовки (content-type/content-length).
		//FOLLOWLOCATION - download.php может редиректить (переезд зеркала/логин слетел),
		//иначе редирект попадёт в ветку 403/503 с неверным варнингом
		$options = array(
			CURLOPT_NOBODY         => 1,
			CURLOPT_HEADER         => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_COOKIE         => kinozalme::$sess_cookie,
			CURLOPT_REFERER        => 'https://kinozal.me/',
		);

		return array(
			'url'     => $url,
			'options' => $options + Sys::getProxyOptions($url),
		);
	}

	//разбираем HEAD-ответ download.php, возвращаем изменения для batchUpdateTorrents или null
	public static function parse($params, $page)
	{
		extract($params);
		$return = NULL;

		//если уже упёрлись в дневной лимит скачиваний или слетел логин - остальные темы в этом
		//цикле не гоняем (запросы уже выполнены curl_multi, но дальнейшую обработку пропускаем)
		if ( ! kinozalme::$exucution)
			return NULL;

		//последняя status-line ответа (если запрос шёл через прокси - их может быть несколько)
		preg_match_all('/^HTTP\/\S+ (\d{3})/m', $page, $codeMatches);
		$code = ! empty($codeMatches[1]) ? (int) end($codeMatches[1]) : 0;

		preg_match('/^content-type:\s*(.+)$/mi', $page, $ctMatch);
		$contentType = isset($ctMatch[1]) ? trim($ctMatch[1]) : '';
		preg_match('/^content-length:\s*(\d+)/mi', $page, $clMatch);
		$contentLength = isset($clMatch[1]) ? (int) $clMatch[1] : NULL;

		if ($code == 200 && stripos($contentType, 'application/x-bittorrent') !== FALSE)
		{
			//download.php отдал .torrent - доступ и логин рабочие
			Database::clearWarnings($tracker);

			$sizes = kinozalme::readSizes();
			$storedSize = isset($sizes[$tracker][$torrent_id]) ? $sizes[$tracker][$torrent_id] : NULL;
			$isNewTopic = empty($timestamp) || $timestamp == '2000-01-01 00:00:00';

			if ($isNewTopic)
				//тема новая (в т.ч. сброшена кнопкой "сброс" в UI - timestamp/hash очищены) -
				//скачиваем торрент независимо от сохранённого размера
				kinozalme::work($id, $tracker, $name, $torrent_id, $hash, $return);
			elseif ($storedSize === NULL)
			{
				//размер ранее не сохранялся (обновление ТМ на этой версии), но тема не новая -
				//запоминаем текущий размер без скачивания, иначе первый цикл после апгрейда
				//потратит дневной лимит скачиваний на все уже добавленные темы
				if ($contentLength !== NULL)
					kinozalme::writeSize($tracker, $torrent_id, $contentLength);
				$return[$id]['error'] = 0;
			}
			elseif ($contentLength !== NULL && $storedSize == $contentLength)
				//размер не изменился - раздача не обновлялась
				$return[$id]['error'] = 0;
			else
				//размер изменился - скачиваем торрент
				kinozalme::work($id, $tracker, $name, $torrent_id, $hash, $return);
		}
		elseif ($code == 200)
		{
			//html вместо .torrent - обычно дневной лимит скачиваний ИЛИ протухшая cookie;
			//используем max_torrent как ближайший существующий варнинг
			if (kinozalme::$warning == NULL)
			{
				kinozalme::$warning = TRUE;
				Errors::setWarnings($tracker, 'max_torrent');
			}
			//останавливаем процесс выполнения
			kinozalme::$exucution = FALSE;
		}
		elseif ($code == 404)
		{
			//раздача не найдена
			if (kinozalme::$warning == NULL)
			{
				kinozalme::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_find_date', $id);
			}
		}
		else
		{
			//403/503/пустой ответ - интерактивный Cloudflare-челлендж или сбой.
			//FlareSolverr здесь не дёргается: у HEAD-ответа в заголовках есть
			//cloudflare-упоминания (server: cloudflare, cf-ray), но isCloudflarePage()
			//требует ЕЩЁ текст challenge-страницы ("Checking your browser"/"DDoS protection"),
			//которого в заголовках нет - фолбэк не сработает, пока эта проверка не ослаблена
			if (kinozalme::$warning == NULL)
			{
				kinozalme::$warning = TRUE;
				Errors::setWarnings($tracker, 'cant_get_forum_page', $id);
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozalme::$exucution = FALSE;
		}

		kinozalme::$warning = NULL;
		return $return;
	}
}
?>
