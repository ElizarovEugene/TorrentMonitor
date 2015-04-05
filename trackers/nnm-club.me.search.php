<?php
include_once('nnm-club.me.engine.php');

class nnmclubSearch extends nnmclub
{
	//ищем темы пользователя
	public static function mainSearch($user_id, $tracker, $user)
	{
		$cookie = Database::getCookie($tracker);
		if (nnmclub::checkCookie($cookie))
		{
			nnmclub::$sess_cookie = $cookie;
			//запускам процесс выполнения
			nnmclub::$exucution = TRUE;
		}
		else
    		nnmclub::getCookie($tracker);

		if (nnmclub::$exucution)
		{
    		$user = iconv('utf-8', 'windows-1251', $user);
    		$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'http://nnm-club.me/forum/tracker.php',
            		'postfields'     => 'prev_sd=0&prev_a=0&prev_my=0&prev_n=0&prev_shc=0&prev_shf=1&prev_sha=1&prev_shs=0&prev_shr=0&prev_sht=0&f%5B%5D=-1&o=1&s=2&tm=-1&shf=1&sha=1&ta=-1&sns=-1&sds=-1&nm=&pn='.$user.'&submit=%CF%EE%E8%F1%EA',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
	        );
	        
	        if ( ! empty($page))
	        {
	        	//сбрасываем варнинг
				Database::clearWarnings($tracker);

	    		preg_match_all('/<a class=\"gen\" href=\"tracker\.php\?f=\d{3,9}\">(.*)<\/a>/', $page, $section);
	    		preg_match_all('/<a class=\"(genmed|leechmed|seedmed) (topicpremod|topictitle)\" href=\"viewtopic\.php\?t=(\d{3,9})\"><b>(.*)<\/b><\/a>/', $page, $threme);
	
	    		for ($i=0; $i<count($threme[1]); $i++)
	    			Database::addThremeToBuffer($user_id, $section[1][$i], $threme[3][$i], $threme[4][$i], $tracker);
	    	}

    		$toDownload = Database::takeToDownload($tracker);
    		if (count($toDownload) > 0)
    		{
                for ($i=0; $i<count($toDownload); $i++)
                {
                	//получаем страницу для парсинга
		            $page = Sys::getUrlContent(
		            	array(
		            		'type'           => 'POST',
		            		'header'         => 0,
		            		'returntransfer' => 1,
		            		'url'            => 'http://nnm-club.me/forum/viewtopic.php?t='.$toDownload[$i]['threme_id'],
		            		'cookie'         => nnmclub::$sess_cookie,
		            		'sendHeader'     => array('Host' => 'nnm-club.me', 'Content-length' => strlen(nnmclub::$sess_cookie)),
		            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
		            	)
		            );

        			if ( ! empty($page))
        			{
                        //находим имя торрента для скачивания
                        if (preg_match('/download\.php\?id=(\d{2,8})/', $page, $link))
                        {
                        	//сбрасываем варнинг
							Database::clearWarnings($tracker);
                            //сохраняем торрент в файл
                            $download_id = $link[1];
            				preg_match('/userid(.*);/U', nnmclub::$sess_cookie, $arr);
                            $uid = $arr[1];
							
							$torrent = Sys::getUrlContent(
                            	array(
                            		'type'           => 'GET',
                            		'returntransfer' => 1,
                            		'url'            => 'http://nnm-club.ws/download.php?csid=&uid='.$uid.'&id='.$download_id,
                            		'cookie'         => nnmclub::$sess_cookie,
                            		'sendHeader'     => array('Host' => 'nnm-club.ws', 'Content-length' => strlen(nnmclub::$sess_cookie)),
                            		'referer'        => 'http://nnm-club.me/forum/viewtopic.php?t='.$torrent_id,
                            	)
                            );
                            $message = $toDownload[$i]['threme'].' добавлена для скачивания.';
                            $status = Sys::saveTorrent($toDownload[$i]['tracker'], $toDownload[$i]['threme_id'], $torrent, $toDownload[$i]['threme_id'], 0, $message, date('d M Y H:i'));
								
							if ($status == 'add_fail' || $status == 'connect_fail' || $status == 'credential_wrong')
							{
							    $torrentClient = Database::getSetting('torrentClient');
							    Errors::setWarnings($torrentClient, $status);
							}	                            
                            
            				//обновляем время регистрации торрента в базе
            				Database::setDownloaded($toDownload[$i]['id']);
                        }
                    }
                }
            }
        }
	}
}
?>