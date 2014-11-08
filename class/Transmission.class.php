<?php

require_once( dirname( __FILE__ ) . '/TransmissionRPC.class.php' );


class Transmission
{
    #добавляем новую закачку в torrent-клиент, обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker)
    {
    	try
    	{
    		#получаем настройки из базы
        	$settings = Database::getAllSetting();
        	foreach ($settings as $row)
        	{
        		extract($row);
        	}
        	if ( ! empty($torrentLogin) && ! empty($torrentPassword))
           	 $rpc = new TransmissionRPC('http://'.$torrentAddress.'/transmission/rpc', $torrentLogin, $torrentPassword);
           	 //$rpc->debug=true;
           	 $result = $rpc->sstats();

        	$individualPath = Database::getTorrentDownloadPath($id);
        	if ( ! empty($individualPath))
	            $pathToDownload = $individualPath;

    	    if ( ! empty($hash))
	        {
    	        $delOpt = 'false';
        	    if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
            	{
                	if ($deleteOldFiles)
                   	$delOpt = 'true';
	            }
            
    	        #удяляем существующую закачку из torrent-клиента
        	     $result = $rpc->remove($hash, $delOpt);
        	}

        	#добавляем торрент в torrent-клиент
          	$result = $rpc->add('/mnt/Data/Files/torrents'.$file, $pathToDownload);
          	$command = $result->result;
          	$idt = $result->arguments->torrent_added->id;
        	if (preg_match('/success/', $command))
        	{
        		#получаем хэш раздачи
        		$result = $rpc->get($idt, array('hashString'));
        		$hashNew = $result->arguments->torrents[0]->hashString;
        		#обновляем hash в базе
        		Database::updateHash($id, $hashNew);
        		
        		//сбрасываем варнинг
        		Database::clearWarnings('Transmission');
        		return 'success';
        	}
        	else
        		return 'add_fail';
    	}
    	catch (Exception $e)
    	{
 //   		die('[ERROR] ' . $e->getMessage() . PHP_EOL);
 			return $e->getMessage();
    	}
    }
}
?>