<?php
include_once('nnmclub.to.engine.php');

class nnmclubSearch extends nnmclub
{
	//ищем темы пользователя
	public static function mainSearch($params)
	{
    	extract($params);
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
    		$user = iconv('utf-8', 'windows-1251', $name);
    		$user = str_replace(' ', '+', $user);
    		$page = Sys::getUrlContent(
            	array(
            		'type'           => 'POST',
            		'header'         => 1,
            		'returntransfer' => 1,
            		'url'            => 'https://nnmclub.to/forum/tracker.php',
            		'postfields'     => 'prev_sd=0&prev_a=0&prev_my=0&prev_n=0&prev_shc=0&prev_shf=1&prev_sha=1&prev_shs=0&prev_shr=0&prev_sht=0&f%5B%5D=-1&o=1&s=2&tm=-1&shf=1&sha=1&ta=-1&sns=-1&sds=-1&nm=&pn='.$user.'&submit=%CF%EE%E8%F1%EA',
            		'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            	)
	        );
	        
	        if ( ! empty($page))
	        {
	        	//сбрасываем варнинг
				Database::clearWarnings($tracker);

	    		preg_match_all('/<a class=\"gen\" href=\"tracker\.php\?f=\d{1,6}\">(.*)<\/a>/', $page, $section);
	    		preg_match_all('/<a class=\"(genmed|leechmed|seedmed) (topicpremod|topictitle)\" href=\"viewtopic\.php\?t=(\d{3,9})\"><b>(.*)<\/b><\/a>/', $page, $threme);
	    		preg_match_all('/<td align=\"center\" nowrap=\"nowrap\" title=\"Добавлено\" class=\"gensmall\"><u>.*<\/u> (\d{2}-\d{2}-\d{4})<br>.*<\/td>/', $page, $dates);

                if (count($section[1]) == count($threme[1]) && count($threme[1]) == count($dates[1]))
                {
	    		    for ($i=0; $i<count($threme[1]); $i++)
	    		    {
    	    		    $arr = preg_split('/-/', $dates[1][$i]);
    	    		    $date = $arr[2].'-'.$arr[1].'-'.$arr[0];
	    			    Database::addThremeToBuffer($id, $section[1][$i], $threme[3][$i], $threme[4][$i], $date, $tracker);
	    			}
                }
	    	}

    		$toDownload = Database::takeToDownload($tracker);
    		if ($toDownload != NULL)
    		{
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
    		            		'url'            => 'https://nnmclub.to/forum/viewtopic.php?t='.$toDownload[$i]['threme_id'],
    		            		'cookie'         => nnmclub::$sess_cookie,
    		            		'sendHeader'     => array('Host' => 'nnmclub.to', 'Content-length' => strlen(nnmclub::$sess_cookie)),
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
                                $torrent = Sys::getUrlContent(
                                	array(
                                		'type'           => 'GET',
                                		'follow'         => 1,
                                		'returntransfer' => 1,
                                		'url'            => 'https://nnmclub.to/forum/download.php?id='.$download_id,
                                		'cookie'         => nnmclub::$sess_cookie,
                                		'sendHeader'     => array('Host' => 'nnmclub.to', 'Content-length' => strlen(nnmclub::$sess_cookie)),
                                		'referer'        => 'http://nnmclub.to/forum/viewtopic.php?t='.$torrent_id,
                                	)
                                );
                                
                                if (Sys::checkTorrentFile($torrent))
                                {
                                    $message = $toDownload[$i]['threme'].' добавлена для скачивания.';
                                    $status = Sys::saveTorrent($tracker, $toDownload[$i]['threme_id'], $torrent, $toDownload[$i]['threme_id'], 0, $message, date('d M Y H:i'), $name);
        								
                    				//обновляем время регистрации торрента в базе
                    				Database::setDownloaded($toDownload[$i]['id']);
                                }
                                else
                                    Errors::setWarnings($tracker, 'torrent_file_fail');
                            }
                        }
                    }
                }
            }
        }
	}
}
?>