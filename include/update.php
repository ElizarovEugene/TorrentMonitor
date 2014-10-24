<?php
include_once('../class/System.class.php');
include_once('../class/Database.class.php');

class Update {
    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file)
            (is_dir($dir.'/'.$file)) ? Update::delTree($dir.'/'.$file) : unlink($dir.'/'.$file);
        return rmdir($dir); 
    }
    
    public static function main()
    {
        $page = Sys::getUrlContent(
        	array(
        		'type'           => 'GET',
        		'returntransfer' => 1,
        		'url'            => 'http://korphome.ru/torrent_monitor/update.xml',
        	)
        );
        
        $xml_page = @simplexml_load_string($page);
        $version = Sys::version();
        $ROOTPATH = str_replace('include', '', dirname(__FILE__));        
        
        for ($i=0; $i<count($xml_page->update->version); $i++)
        {
            $updVersion = $xml_page->update->version[$i];
            $description = $xml_page->update->description[$i];
            $files = $xml_page->update->files[$i];
            $queryes = $xml_page->update->queryes[$i];
            
            if ($version < $updVersion)
            {
                echo '<b>Changelog for '.$updVersion.':</b></br>';
                echo $description.'<br>';
                
                $file = file_get_contents('https://github.com/ElizarovEugene/TorrentMonitor/archive/master.zip');
                if ( ! empty($file))
                {
                    if (file_put_contents($ROOTPATH.'master.zip', $file))
                    {
                        $zip = new ZipArchive;
                        if ($zip->open($ROOTPATH.'master.zip') === TRUE)
                        {
                            $zip->extractTo($ROOTPATH.'tmp');
                            $zip->close();
                            unlink($ROOTPATH.'master.zip');
                	        foreach($files->file as $file)
                	        {
                	            if ( ! copy($ROOTPATH.'tmp/TorrentMonitor-master/'.$file, $ROOTPATH.$file))
                	            {
                	                echo 'Не удалось скопировать файл: '.$file.", обновление прервано.<br>";
                	                break;
                                }
                                else
                                    echo 'Файл: '.$file.' обновлён.<br>';
                            }
                            Update::delTree($ROOTPATH.'tmp');
                            $x=0;
                            foreach($queryes->query as $query)
                	        {
                	            Database::updateQuery($query);
                	            $x++;
                            }
                            echo 'Выполнено '.$x.' запросов на обновление.<br>';           
                        }
                        else
                            echo 'Не могу разархивировать master.zip<br>';
                    }
                    else
                        echo 'Не могу сохранить master.zip<br>';
                }
                else
                    echo 'Не удалось скачать master.zip<br>';
            }
            else
                echo 'Перейти на <a href="http://'.$_SERVER["HTTP_HOST"].'">главную страницу</a>.<br>';
        }
    }
}

Update::main();
?>