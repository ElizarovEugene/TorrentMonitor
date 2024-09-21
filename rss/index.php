<?php
$dir = dirname(__FILE__).'/../';
include_once $dir.'class/Database.class.php';

$rss = Database::getSetting('rss');

if ($rss)
{
    header('Content-type: text/xml');
    
    //количество записей в ленте. При необходимости, можно вынести в параметры
    $maxCount = 20;
    
    #$url = 'http://'.$_SERVER["HTTP_HOST"].str_replace('/rss/index.php', '', $_SERVER["SCRIPT_NAME"]);
    $url = Database::getSetting('serverAddress');
    
    $xml=new DomDocument('1.0','utf-8'); // создаем XML документ
    $xml->formatOutput = true;           // включаем форматирование документа
    
    // формируем корневой элемент ('rss')
    $root = $xml->appendChild($xml->createElement('rss'));
    $root->setAttribute('version','0.91');
    
    // формируем элемент 'channel'
    $channel = $root->appendChild($xml->createElement('channel'));
    
    ////////////////////////////////////
    // Заполняем заголовок документа
    
    // формируем элемент 'title'
    $title = $channel->appendChild($xml->createElement('title'));
    $title->appendChild($xml->createTextNode('TorrentMonitor RSS'));
    
    // формируем элемент 'link'
    $link = $channel->appendChild($xml->createElement('link'));
    $link->appendChild($xml->createTextNode($url.'rss/'));

    // формируем элемент 'language'
    $language = $channel->appendChild($xml->createElement('language'));
    $language->appendChild($xml->createTextNode('ru'));
    
    ////////////////////////////////////////////////////////////////////////
    // Подготавливаем список *torrent файлов для заполнения тела докумена
    
    // формируем ассоциативный массив, где ключем выступает имя файла, а значением время изменения
    $torrentsList = array();
    foreach (glob($dir.'torrents/*.torrent') as $torrentFile)
    {
        $fileName = basename($torrentFile);
        $timestam = filemtime($torrentFile);
        
        $torrentsList[$fileName] = $timestam;
    }
    
    // выполняем сортировку массива в порядке убывания значения
    arsort($torrentsList);
    
    // формируем элемент 'lastBuildDate'
    if (count($torrentsList)) {
        $first = reset($torrentsList);
        
        $lastBuildDate = $channel->appendChild($xml->createElement('lastBuildDate'));
        $lastBuildDate->appendChild($xml->createTextNode(date("r", $first)));
    }
    
    ////////////////////////////////////
    // Заполняем тело документа
    
    //инициализируем счетчик
    $count = 1;
    
    // делаем обход по элементам массива
    foreach ($torrentsList as $fileName => $fileDate)
    {    
        // формируем элемент 'item'
        $item = $channel->appendChild($xml->createElement('item'));
        
        // формируем элемент 'title'
        $title = $item->appendChild($xml->createElement('title'));
        $title->appendChild($xml->createTextNode(sprintf("%0d. ", $count++).$fileName));
        
        // формируем элемент 'pubDate'
        $pubDate = $item->appendChild($xml->createElement('pubDate'));
        $pubDate->appendChild($xml->createTextNode(date("r", $fileDate)));
        
        // формируем элемент 'link'
        $link = $item->appendChild($xml->createElement('link'));
        $link->appendChild($xml->createTextNode($url.'torrents/'.$fileName));
        
        if ($count > $maxCount)
            break;
    } 
    
    // выводим содержимое XML документа
    print $xml->saveXML();
}
else
    echo '<h1>RSS disabled</h1>';
?>
