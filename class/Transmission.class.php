<?php
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
        $opt = '';
        if ( ! empty($torrentLogin) && ! empty($torrentPassword))
            $opt = '-n '.$torrentLogin.':'.$torrentPassword;

        $individualPath = Database::getTorrentDownloadPath($id);
        if ( ! empty($individualPath))
            $pathToDownload = $individualPath;

        if ( ! empty($hash))
        {
            $delOpt = '-r';
            if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
            {
                if ($deleteOldFiles)
                    $delOpt = '--remove-and-delete';
            }
            
            #удяляем существующую закачку из torrent-клиента
            $command = `transmission-remote $torrentAddress $opt -t $hash $delOpt`;
        }

        #добавляем торрент в torrent-клиента
        $command = `transmission-remote $torrentAddress $opt -a '$file' -w '$pathToDownload'`;
        if ( ! preg_match('/responded\:\s\"success\"/', $command))
            return 'add_fail';
        elseif (preg_match('/Couldn\'t connect to server/', $command))
            return 'connect_fail';
        else
        {
            #получаем хэш раздачи
            $hashNew = `transmission-show '$file' | grep Hash | awk '{print $2}'`;
            #обновляем hash в базе
            Database::updateHash($id, $hashNew);
        
            //сбрасываем варнинг
            Database::clearWarnings('Transmission');
            return TRUE;
        }
    }
}
?>