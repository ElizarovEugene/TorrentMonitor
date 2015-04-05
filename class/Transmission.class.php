<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'TransmissionRPC.class.php';

class Transmission
{
    #добавляем новую закачку в torrent-клиент, обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker)
    {
        #получаем настройки из базы
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
        	extract($row);
        }

	try
	{
    	    $rpc = new TransmissionRPC('http://'.$torrentAddress.'/transmission/rpc', $torrentLogin, $torrentPassword);
    	    #$rpc->debug=true;
    	    $result = $rpc->sstats();

    	    $individualPath = Database::getTorrentDownloadPath($id);
    	    if ( ! empty($individualPath))
        	$pathToDownload = $individualPath;

    	    if ( ! empty($hash))
    	    {
        	$delOpt = 'false';
        	if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv' || $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
        	{
            	    if ($deleteOldFiles)
                	$delOpt = 'true';
            	    #удяляем существующую закачку из torrent-клиента
            	    if ($deleteDistribution)
                	$result = $rpc->remove($hash, $delOpt);                    
        	}
        	else
        	{
            	    #удяляем существующую закачку из torrent-клиента
            	    $result = $rpc->remove($hash, $delOpt);
        	}
    	    }

            #добавляем торрент в torrent-клиент
            $result = $rpc->add($file, $pathToDownload);
            $command = $result->result;
            $idt = @$result->arguments->torrent_added->id;
        
            if (preg_match('/Couldn\'t connect to server/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
            }
            elseif (preg_match('/No Response/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'no_response';
            }
            elseif (preg_match('/invalid or corrupt torrent file/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'torrent_file_fail';
            }
            elseif (preg_match('/duplicate torrent/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'duplicate_torrent';
            }
            elseif (preg_match('/gotMetadataFromURL: http error 404: Not Found/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = '404';
            }
            elseif (preg_match('/gotMetadataFromURL: http error 401: Unauthorized/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'unauthorized';
            }
            elseif (preg_match('/username/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
            }
            elseif (preg_match('/success/', $command))
            {
	        #получаем хэш раздачи
                $result = $rpc->get($idt, array('hashString'));
                $hashNew = $result->arguments->torrents[0]->hashString;
                #обновляем hash в базе
                Database::updateHash($id, $hashNew);
            
                //сбрасываем варнинг
                Database::clearWarnings('Transmission');
                $return['status'] = TRUE;
            }
            else
            {
        	$return['status'] = FALSE;
        	$return['msg'] = 'unknown';
    	    }
        }
        catch (Exception $e)
        {
    	    if (preg_match('/Invalid username\/password./', $e->getMessage()))
    	    {
    		$return['status'] = FALSE;
    		$return['msg'] = 'log_passwd';
    	    }
    	    elseif (preg_match('/Unable to connect/', $e->getMessage()))
    	    {
    		$return['status'] = FALSE;
    		$return['msg'] = 'connect_fail';
    	    }
    	    else
    		die('[ERROR] ' . $e->getMessage() . PHP_EOL);
    	    }

    	return $return;
    }
}
?>