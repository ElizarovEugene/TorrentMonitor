<?php
class qBittorrent
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

        $data = array('username'=>$torrentLogin,'password'=>$torrentPassword);

        //Авторизация
        $MainCurl = curl_init();
        curl_setopt_array($MainCurl, array(
            CURLOPT_URL => "$torrentAddress/api/v2/auth/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));
    
        $response=curl_exec($MainCurl);
    
        preg_match_all("/SID=(.*?);/",$response,$match);
        $cookie = "SID=".$match[1][0];
        curl_setopt($MainCurl,CURLOPT_COOKIE,$cookie);
        curl_setopt($MainCurl,CURLOPT_HEADER,false);

        preg_match_all("/.*\/(.*)/",$file,$match);
        $filename = $match[1][0];
            
        if ( ! empty($hash))
        {
            $data = array(
                'hashes'=>$hash,
                'deleteFiles'=>'false'
            );
            curl_setopt($MainCurl, CURLOPT_URL,"$torrentAddress/api/v2/torrents/delete");
            curl_setopt($MainCurl, CURLOPT_POSTFIELDS, http_build_query($data));

            if ($tracker == 'lostfilm.tv' || $tracker == 'lostfilm-mirror' ||  $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
            {
                if ($deleteOldFiles)
                    $data['deleteFiles'] = 'true';
                #удяляем существующую закачку из torrent-клиента
                if ($deleteDistribution)
                    curl_exec($MainCurl);
            }
            else
            {
                #удяляем существующую закачку из torrent-клиента
                curl_exec($MainCurl);
            }
        }
        
        //Формируется тело запроса
        $data = array();
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"torrents\"; filename=\"$filename\";",
            "Content-Type: application/x-bittorrent",
            "",
            file_get_contents($file)
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"autoTMM\"",
            "",
            "false"
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"savepath\"",
            "",
            "$pathToDownload"
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"rename\"",
            "",
            ""
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"category\"",
            "",
            ""
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"paused\"",
            "",
            "false"
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"root_folder\"",
            "",
            "true"
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"sequentialDownload\"",
            "",
            "true"
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"dlLimit\"",
            "",
            ""
        ));
        $data[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"upLimit\"",
            "",
            ""
        ));

        //генерируется разрыв
        do {
            $boundary = "---------------------" . md5(mt_rand() . microtime());
        } while (preg_grep("/{$boundary}/", $data));

        //добавляется разрыв к каждому параметру
        array_walk($data, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });

        //добавляется последний разрыв
        $data[] = "--{$boundary}--";
        $data[] = "";

        //формируется заголовок запроса
        $request_headers = array();
        $request_headers[] = "Cookie: $cookie";
        $request_headers[] = "Content-Type: multipart/form-data; boundary={$boundary}";

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $torrentAddress."/api/v2/torrents/add",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $request_headers,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_POSTFIELDS => implode("\r\n", $data),
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/Ok/', $response)) {
            //получение хэша торрента
            $data = array(
                'filter'=>'all',
                'limit'=>'1',
                'sort'=>'added_on',
                'reverse'=>'true'
            );
            curl_setopt($MainCurl, CURLOPT_URL,"$torrentAddress/api/v2/torrents/info");
            curl_setopt($MainCurl, CURLOPT_POSTFIELDS, http_build_query($data));
            $response = curl_exec($MainCurl);
            $rdata = json_decode($response)[0];
            $hashNew = $rdata->hash;

            #обновляем hash в базе
            Database::updateHash($id, $hashNew);

            //сбрасываем варнинг
            Database::clearWarnings('qBittorrent');
            $return['status'] = TRUE;
            $return['hash'] = $hashNew;
        } else {
            $return['status'] = FALSE;
            $return['msg'] = 'add_fail';
        }

        //выход
        curl_setopt($MainCurl,CURLOPT_URL,"$torrentAddress/logout");
        curl_exec($MainCurl);
        curl_close($MainCurl);

        return $return;
    }
}
?>
