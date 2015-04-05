<?php
class kinozal
{
	protected static $sess_cookie;
	protected static $exucution;
	protected static $warning;

	//проверяем cookie
	public static function checkCookie($sess_cookie)
	{
        $result = Sys::getUrlContent(
        	array(
        		'type'           => 'POST',
        		'returntransfer' => 1,
        		'url'            => 'http://kinozal.tv',
        		'cookie'         => $sess_cookie,
        		'sendHeader'     => array('Host' => 'kinozal.tv', 'Content-length' => strlen($sess_cookie)),
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
	
	//функция преобразования даты
	private static function dateStringToNum($data)
	{
	    if (strstr($data, 'сегодня') || strstr($data, 'вчера') || strstr($data, 'сейчас'))
	    {
	        $pieces = explode(' ', $data);
	        if ($pieces[0] == 'вчера')
	            $timestamp = strtotime('-1 day');
	        else         
	            $timestamp = strtotime('now');
	        $date = date('Y-m-d', $timestamp);
	        if (strstr($data, 'сейчас'))
	            $time = date('G:i').':00';
            else
	            $time = $pieces[2].':00';
	        $dateTime = $date.' '.$time;

	        return $dateTime;
	    }
	    elseif (preg_match('/\d{1,2} \D* \d{4} в \d{2}:\d{2}/', $data))
	    {
			$pieces = explode(' ', $data);
			$month = Sys::dateStringToNum(substr($pieces[1], 0, 6));
			if (strlen($pieces[0]) == 1)
			    $pieces[0] = '0'.$pieces[0];
			$date = $pieces[2].'-'.$month.'-'.$pieces[0];
			$time = $pieces[4].':00';
			$dateTime = $date.' '.$time;

			return $dateTime;
	    }
	}

	//функция преобразования даты
	private static function dateNumToString($data)
	{
	    if (strstr($data, 'сегодня') || strstr($data, 'вчера'))
	    {
	        $pieces = explode(' ', $data);
	        if ($pieces[0] == 'вчера')
	            $timestamp = strtotime('-1 day');
	        else         
	            $timestamp = strtotime('now');
	        $day = date('d', $timestamp);
			$month = Sys::dateNumToString(date('m', $timestamp));
			$year = date('Y', $timestamp);
	        $dateTime = $day.' '.$month.' '.$year.' в '.$pieces[2];
	        return $dateTime;
	    }
	    elseif (strstr($data, 'сейчас'))
	    {
	        $timestamp = strtotime('now');
	        $day = date('d', $timestamp);
			$month = Sys::dateNumToString(date('m', $timestamp));
			$year = date('Y', $timestamp);
			$time = date('G:i').':00';
	        $dateTime = $day.' '.$month.' '.$year.' в '.$time;
	        return $dateTime;
        }
	   	else
			return $data;
	}	

	//функция получения кук
	protected static function getCookie($tracker)
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
            		'url'            => 'http://kinozal.tv/takelogin.php',
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
					kinozal::$exucution = FALSE;
				}
				//проверяем нет ли блокировки
				if (preg_match('/Превышен лимит попыток входа в профиль <br>Попробуйте через 2 часа/', $page, $array))
				{
					//устанавливаем варнинг
					Errors::setWarnings($tracker, 'limit');
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = FALSE;
				}
				//если подходят - получаем куки
				elseif (preg_match_all('/Set-Cookie: (.+);/iU', $page, $array))
				{
					kinozal::$sess_cookie = $array[1][1].'; '.$array[1][2].';';
					Database::setCookie($tracker, kinozal::$sess_cookie);
					//запускам процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = TRUE;
				}
				else
				{
					//устанавливаем варнинг
					if (kinozal::$warning == NULL)
					{
						kinozal::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без кук
					kinozal::$exucution = FALSE;
				}
			}
			//если вообще ничего не найдено
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozal::$warning == NULL)
			{
				kinozal::$warning = TRUE;
				Errors::setWarnings($tracker, 'credential_miss');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozal::$exucution = FALSE;
		}
	}
	
    public static function work($array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
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
				$date = kinozal::dateStringToNum($array[1]);
				$date_str = kinozal::dateNumToString($array[1]);
				//если даты не совпадают, перекачиваем торрент
				if ($date > $timestamp)
				{
					//сохраняем торрент в файл
                    $torrent = Sys::getUrlContent(
                    	array(
                    		'type'           => 'POST',
                    		'returntransfer' => 1,
                    		'url'            => 'http://kinozal.tv/download.php?id='.$torrent_id,
                    		'cookie'         => kinozal::$sess_cookie,
                    		'sendHeader'     => array('Host' => 'kinozal.tv', 'Content-length' => strlen(kinozal::$sess_cookie)),
                    		'referer'        => 'http://kinozal.tv/details.php?id='.$torrent_id,
                    	)
                    );
					if (preg_match('/<a href=\'\/pay_mode\.php\#tcounter\' class=sbab>/', $torrent))
					{
        				//устанавливаем варнинг
        				if (kinozal::$warning == NULL)
        				{
        					kinozal::$warning = TRUE;
        					Errors::setWarnings($tracker, 'max_torrent');
        				}
        				//останавливаем процесс выполнения
        				kinozal::$exucution = FALSE;
					}
					else
					{

    					if ($auto_update)
						{
						    $name = Sys::getHeader('http://kinozal.tv/details.php?id='.$torrent_id);
						    //обновляем заголовок торрента в базе
                            Database::setNewName($id, $name);
						}

    					$message = $name.' обновлён.';
    					$status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
								
    					//обновляем время регистрации торрента в базе
    					Database::setNewDate($id, $date);
    				}
				}
			}
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
		else
		{
			//устанавливаем варнинг
			if (kinozal::$warning == NULL)
			{
				kinozal::$warning = TRUE;
				Errors::setWarnings($tracker, 'not_available');
			}
			//останавливаем процесс выполнения, т.к. не может работать без кук
			kinozal::$exucution = FALSE;
		}
    }
	
	//основная функция
	public static function main($id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update)
	{
		$cookie = Database::getCookie($tracker);
		if (kinozal::checkCookie($cookie))
		{
			kinozal::$sess_cookie = $cookie;
			//запускам процесс выполнения
			kinozal::$exucution = TRUE;
		}			
		else
    		kinozal::getCookie($tracker);

		if (kinozal::$exucution)
		{
			//получаем страницу для парсинга
            $page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 0,
            		'returntransfer' => 1,
            		'url'            => 'http://kinozal.tv/details.php?id='.$torrent_id,
            		'cookie'         => kinozal::$sess_cookie,
            		'sendHeader'     => array('Host' => 'kinozal.tv', 'Content-length' => strlen(kinozal::$sess_cookie)),
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
            );			

			if ( ! empty($page))
			{
				//ищем на странице дату регистрации торрента
				if (preg_match('/<li>Обновлен<span class=\"floatright green n\">(.*)<\/span><\/li>/', $page, $array))
    				kinozal::work($array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update);
				elseif (preg_match('/<li>Залит<span class=\"floatright green n\">(.*)<\/span><\/li>/', $page, $array))
				    kinozal::work($array, $id, $tracker, $name, $torrent_id, $timestamp, $hash, $auto_update);
				else
				{
					//устанавливаем варнинг
					if (kinozal::$warning == NULL)
					{
						kinozal::$warning = TRUE;
						Errors::setWarnings($tracker, 'not_available');
					}
					//останавливаем процесс выполнения, т.к. не может работать без даты
					kinozal::$exucution = FALSE;
				}
			}			
			else
			{
				//устанавливаем варнинг
				if (kinozal::$warning == NULL)
				{
					kinozal::$warning = TRUE;
					Errors::setWarnings($tracker, 'not_available');
				}
				//останавливаем процесс выполнения, т.к. не может работать без кук
				kinozal::$exucution = FALSE;
			}
		}
	}
}
?>
