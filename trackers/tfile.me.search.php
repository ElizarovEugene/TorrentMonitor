<?php
include_once('tfile.me.engine.php');

class tfileSearch extends tfile
{
	//ищем темы пользователя	
	public static function mainSearch($user_id, $tracker, $user)
	{
		$user = str_replace(' ', '+', iconv('utf-8', 'windows-1251', $user));
        //получаем страницу для парсинга
    	$page = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'header'         => 0,
        		'returntransfer' => 1,
        		'url'            => 'http://tfile.me/forum/ssearch.php?a='.$user.'&to=1&io=1',
        		'convert'        => array('windows-1251', 'utf-8')
        	)
        );
        
        if ( ! empty($page))
		{
			//сбрасываем варнинг
			Database::clearWarnings($tracker);
			if (preg_match_all('/<td class=\"f\">\n\t\t\t\t\n\t\t\t\t\t(.*)\n\t\t\t\t<\/td>/', $page, $section))
			{
				for ($i=0; $i<count($section[1]); $i++)
				{
					if (preg_match_all('/<a href=\"\/forum\/viewforum\.php\?f=\d{1,9}\">(.*)<\/a>/U', $section[1][$i], $sections))
					{
	    				$sectionStr = '';
	    				for ($x=0; $x<count($sections[1]); $x++)
	    					$sectionStr .= $sections[1][$x].', ';
	
	    				$sectionStr = substr($sectionStr, 0, -2);
	    				$sectionArr[] = $sectionStr;
	                }
				}
			}

			if (preg_match_all('/<a href=\"\/forum\/viewtopic\.php\?t=(\d{1,9})\">(.*)<\/a>/U', $page, $threme))
	        {
				for ($i=0; $i<count($threme[1]); $i++)
					Database::addThremeToBuffer($user_id, $sectionArr[$i], $threme[1][$i], $threme[2][$i], $tracker);
			}
		}

		$toDownload = Database::takeToDownload($tracker);
		if (count($toDownload) > 0)
		{
            for ($i=0; $i<count($toDownload); $i++)
            {
            	//получаем страницу для парсинга
            	$page = Sys::getUrlContent(
	            	array(
	            		'type'           => 'GET',
	            		'header'         => 0,
	            		'returntransfer' => 1,
	            		'url'            => 'http://tfile.me/forum/viewtopic.php?t='.$toDownload[$i]['threme_id']
	            	)
	            );
                
				//находим имя торрента для скачивания		
				if (preg_match('/download\.php\?id=(\d+)&uk=1111111111/', $page, $link))
				{
					//сбрасываем варнинг
					Database::clearWarnings($tracker);
					//ищем на странице id торрента
					$download_id = $link[1];
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
}