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
            if ($debug)
        	    $rpc->debug=true;
        	$result = $rpc->sstats();
    
        	$individualPath = Database::getTorrentDownloadPath($id);
        	if ( ! empty($individualPath))
            	$pathToDownload = $individualPath;

        	if ( ! empty($hash))
        	{
            	$delOpt = 'false';
            	if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' || $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
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

            if (isset($result->arguments->torrent_added))
            {
                $hashNew = $result->arguments->torrent_added->hashString;
                #обновляем hash в базе
                Database::updateHash($id, $hashNew);
                
                //сбрасываем варнинг
                Database::clearWarnings('Transmission');
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;
            }
            
            elseif (isset($result->arguments->torrent_duplicate))
            {
                $hashNew = $result->arguments->torrent_duplicate->hashString;
                #обновляем hash в базе
                Database::updateHash($id, $hashNew);
                
                //сбрасываем варнинг
                Database::clearWarnings('Transmission');
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;                
            }
            elseif (preg_match('/invalid or corrupt torrent file/i', $result->result))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'torrent_file_fail';
            }
            elseif (preg_match('/http error 0: No Response/i', $result->result))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'no_response';
            }
            else
            {
        	    $return['status'] = FALSE;
                $return['msg'] = 'unknown';
    	    }
        }
        catch (Exception $e)
        {
    	    if (preg_match('/Invalid username\/password\./', $e->getMessage()))
    	    {
    		    $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
    	    }
    	    elseif (preg_match('/Unable to connect to/U', $e->getMessage()))
    	    {
    		    $return['status'] = FALSE;
                $return['msg'] = 'connect_fail';
    	    }
    	    else
    	    {
        	    $return['status'] = FALSE;
        	    $return['msg'] = 'unknown';
    		    echo '[ERROR]'.$e->getMessage().PHP_EOL;
            }
        }
    	return $return;
    }
}
?>