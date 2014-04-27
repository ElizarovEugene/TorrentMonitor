<?php
class Deluge
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
        $individualPath = Database::getTorrentDownloadPath($id);
        if ( ! empty($individualPath))
            $pathToDownload = $individualPath;

        if ( ! empty($hash))
        {
            $delOpt = '';
            if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
            {
                if ($deleteOldFiles)
                    $delOpt = '--remove_data';
            }
                
            #удяляем существующую закачку из torrent-клиента
            $command = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; rm $hash $delOpt'`;
        }

        #добавляем торрент в torrent-клиента
        $command = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; add -p '$pathToDownload' $file'`;
        if ( ! preg_match('/Torrent added!/', $command))
        {
            Errors::setWarnings('Deluge', 'add_fail');
        }
        else
        {
            #получаем хэш раздачи
            $hashNew = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; info --sort-reverse=active_time' | grep ID: | awk '{print $2}' | tail -n -1`;
            #обновляем hash в базе
            Database::updateHash($id, $hashNew);
        
            //сбрасываем варнинг
            Database::clearWarnings('Deluge');
        }
    }
}
?>