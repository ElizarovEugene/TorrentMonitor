<?php
$dir = dirname(__FILE__).'/';

class TorrServer
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
            if ( ! empty($hash))
            {
                if ($deleteDistribution)
                {
                    $data = 
                        array(
                            "action" => "rem",
                            "hash" => $hash
                        );
                    $data = json_encode($data);
                                        
                    $request_headers[] = "Content-Type: application/json";
                    
                    $ch = curl_init();
                    curl_setopt_array($ch, array(
                        CURLOPT_POST => 1,
                        CURLOPT_FOLLOWLOCATION => 1,
                        CURLOPT_URL => 'http://'.$torrentAddress.'/torrents/',
                        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:51.0) Gecko/20100101 Firefox/51.0',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_USERPWD => $torrentLogin.':'.$torrentPassword,
                        CURLOPT_HTTPHEADER => $request_headers,
                        CURLOPT_POSTFIELDS => $data,
                    ));
                    $response = curl_exec($ch);
                    curl_close($ch);
                }
            }
            
            $torrent = Database::getTorrent($id);
            if ($torrent)
                $name = str_replace(' ', '.', $torrent[0]['name']);
            else
                $name = '';

            #добавляем торрент в torrent-клиент
            $url = 'http://'.$torrentAddress.'/stream/fname?link='.$file.'&save&title='.$name.'&stat';
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:51.0) Gecko/20100101 Firefox/51.0',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $torrentLogin.':'.$torrentPassword,
            ));
            $response = curl_exec($ch);
            if ($debug)
                var_dump($response);
            curl_close($ch);
            
            preg_match_all('/\"hash\":\"(.*)\"/U', $response, $res);
            if ($res[1])
            {
                $hashNew = $res[1][0];
    
                Database::updateHash($id, $hashNew);
                Database::clearWarnings('TorrServer');
                
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;
            }
            else
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
            }
        }
        catch (Exception $e)
        {
            echo $e->getMessage().PHP_EOL;
        }
        
        return $return;
    }
}
?>