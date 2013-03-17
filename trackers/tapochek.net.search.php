<?php
include_once('tapochek.net.engine.php');

class tapochekSearch extends tapochek
{
	
	//получаем страницу для парсинга
	private static function getSearchPage($user, $sess_cookie)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; ru; rv:1.9.2.4) Gecko/20100611 Firefox/3.6.4");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "http://tapochek.net/tracker.php");
		$header[] = "Host: tapochek.net\r\n";
		$header[] = "Content-length: ".strlen($sess_cookie)."\r\n\r\n";
		curl_setopt($ch, CURLOPT_COOKIE, $sess_cookie);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "prev_allw=1&prev_a=0&prev_ts_checked=0&prev_ts_not_checked=0&prev_ts_closed=0&prev_ts_d=0&prev_ts_not_perfect=0&prev_ts_part_perfect=0&prev_ts_fishily=0&prev_ts_copy=0&prev_ts_pogl=0&prev_ts_proverka=0&prev_ts_ideal_rip=0&prev_ts_toch_rip=0&prev_ts_mb_ne_toch_rip=0&prev_ts_ne_toch_rip=0&prev_ts_tmp=0&prev_gold=0&prev_silver=0&prev_dla=0&prev_dlc=0&prev_dld=0&prev_dlw=0&prev_my=0&prev_new=0&prev_sd=0&prev_da=1&prev_dc=0&prev_df=1&prev_ds=0&save_profile=0&profile_name=&f%5B%5D=-1&o=1&s=2&tm=-1&sns=-1&df=1&da=1&pn={$user}&nm=&allw=1&submit=%A0%A0%CF%EE%E8%F1%EA%A0%A0");
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = iconv("windows-1251", "utf-8", $result);
		return $result;
	}
	
	public static function mainSearch($user_id, $tracker, $user)
	{
		$cookie = Database::getCookie($tracker);
		if (tapochek::checkCookie($cookie))
		{
			tapochek::$sess_cookie = $cookie;
			//запускам процесс выполнения
			tapochek::$exucution = TRUE;
		}			
		else
		{
    		tapochek::getCookie($tracker);
    		if (tapochek::$sess_cookie == 'deleted')
    			tapochek::getCookie($tracker);
		}
		
        if (tapochek::$exucution)
		{			
    		$user = iconv("utf-8", "windows-1251", $user);
    		$page = tapochekSearch::getSearchPage($user, tapochek::$sess_cookie);
    		
    		preg_match_all('/<a class=\"gen\" href=\"tracker\.php\?f=\d{1,9}&nm=&pid=\d{1,9}\">(.*)<\/a>/', $page, $section);
    		preg_match_all('/<a class=\"genmed\"  href=\"\.\/viewtopic\.php\?t=(\d{3,9})\"><b>(.*)<\/b><\/a>/', $page, $threme);
    		
    		for ($i=0; $i<count($threme[1]); $i++)
    		{
    			Database::addThremeToBuffer($user_id, $section[1][$i], $threme[1][$i], $threme[2][$i], $tracker);
    		}

    		$toDownload = Database::takeToDownload($tracker);
    		if (count($toDownload) > 0)
    		{
                for ($i=0; $i<count($toDownload); $i++)
                {
                    tapochek::$page = tapochek::getContent($toDownload[$i]['threme_id'], tapochek::$sess_cookie);
			
        			if ( ! empty(tapochek::$page))
        			{
                        //находим имя торрента для скачивания		
                        if (preg_match("/download\.php\?id=(\d{2,8})/", tapochek::$page, $link))
                        {
                            //сохраняем торрент в файл
                            $torrent_id = $link[1];
            				$torrent = tapochek::getTorrent($torrent_id, tapochek::$sess_cookie);
            				$client = ClientAdapterFactory::getStorage('file');
            				$client->store($torrent, $toDownload[$i]['threme_id'], $tracker, $toDownload[$i]['threme'], $torrent_id, time());
            				//обновляем время регистрации торрента в базе
            				Database::setDownloaded($toDownload[$i]['id']);
            				//отправляем уведомлении о новом торренте
            				$message = $toDownload[$i]['threme'].' добавлена для скачивания.';
            				$date = date('d M Y H:i');
            				Notification::sendNotification('notification', $date, $tracker, $message);
                        }
                    }
                }
            }
        }
	}
}