<?php
class Transmission
{
    #добавляем новую закачку в torrent-клиент, обновляем hash в базе
    public static function addNew($id, $file, $hash, $tracker, $download_path='')
    {
        #получаем настройки из базы
        $settings = Database::getAllSetting();
        foreach ($settings as $row)
        {
        	extract($row);
        }
        if( $download_path){
            $pathToDownload = $download_path;
        }

        $opt = '';
        if ( ! empty($torrentLogin) && ! empty($torrentPassword))
            $opt = '-n '.$torrentLogin.':'.$torrentPassword;

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
        $command = `transmission-remote $torrentAddress $opt -a '$file' -w $pathToDownload`;
        echo $command, "\n";
        if ( ! preg_match('/responded: \"success\"/', $command))
        {
            Errors::setWarnings('Transmission', 'add_fail');
        }
        else
        {
            #получаем хэш раздачи
            $hashNew = `transmission-show '$file' | grep Hash | awk '{print $2}'`;
            #обновляем hash в базе
            Database::updateHash($id, $hashNew);
        
            //сбрасываем варнинг
            Database::clearWarnings('Transmission');
        }
    }
}
?>