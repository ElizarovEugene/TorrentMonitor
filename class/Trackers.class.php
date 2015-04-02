<?php
$dir = str_replace('class', '', dirname(__FILE__));

// выполняем подключение классов engine
foreach (glob($dir."trackers/*.engine.php") as $trackerEngine) {
    include_once $trackerEngine;
    
    $tracker = str_replace('.engine.php', '', basename($trackerEngine));
    Trackers::addTracker($tracker, 'engine');
}
    
// выполняем подключение классов search
foreach (glob($dir."trackers/*.search.php") as $trackerEngine) {
    include_once $trackerEngine;

    $tracker = str_replace('.search.php', '', basename($trackerEngine));
    Trackers::addTracker($tracker, 'search');
}

class Trackers {
    
    public static $trackersList    = array(); // массив хранит информацию о всех подключенных треккерах
    public static $associationList = array(); // массив хранит все связку треккера и доменного имени
    
    // функция возвращает класс треккера, тип и имя которого передано в параметрах
    // параметры:
    //    tracker   - имя треккера, для которого необходимо получить класс
    //    classType - тип возвращаемого класса. Допустимые значения 'engine' и 'search'
    // результат:
    //    возвращается класс для треккера.
    private static function getClassName($tracker, $classType = '')
    {
        // по типу класса определяем суффикс имени
        $suffix = '';
        if ($classType == 'search')
            $suffix = 'Search';
        
        // преобразовываем имя треккера в имя класса
        $className = mb_strtolower( str_replace(array('.', '-'), '', $tracker) ).$suffix;
        
        if  (!class_exists($className))
            return null;
        
        return $className;
    }
    
    // процедура выполняет заполнение списка ассоциаций
    // параметры:
    //    tracker - имя треккера
    //    classType - тип возвращаемого класса. Допустимые значения 'engine' и 'search'
    public static function addTracker($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        $trackerType = Trackers::getTrackerType($tracker, $classType);
        // добавляем треккер в список
        Trackers::$trackersList[] = array(
                                        'tracker' => $tracker,
                                        'type' => $trackerType,
                                    );
        
        // добавляем ассоциацию сам с собой
        Trackers::$associationList[$tracker] = $tracker;
        
        //если у класса есть метод 'getAssociations', то получаем список связанных имен и подвязываем в список
        if ( method_exists($trackerClass, 'getAssociations') ) {
            $associations = $trackerClass::getAssociations();
            foreach($associations as $association)
                Trackers::$associationList[$association] = $tracker;
        }
    }
    
    // функция выполняет проверку на наличие модуля для треккера
    // параметры:
    //    tracker - имя треккера
    // результат:
    //    возвращается класс для треккера.
    public static function moduleExist($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
        return ($trackerClass != null);
    }
    
    // функция определяет тип треккера
    // параметры:
    //    tracker - имя треккера
    // результат:
    //    возвращает тип треккера.
    public static function getTrackerType($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
        if ($trackerClass != null && method_exists($trackerClass, 'getTrackerType'))
            return $trackerClass::getTrackerType();
        else if ($trackerClass != null && $classType == 'search')
            return 'search';
        else if ($trackerClass != null)
            return 'threme';
        else
            return null;
    }
    
    // функция выполняет проверку раздачи на обновление
    // параметры:
    //    classType   - тип класса для проверки запуска проверки. Допустимые значения 'engine' и 'search'
    //    tracker     - имя треккера
    //    torrentInfo - массив с параметрами раздачи
    // результат:
    //    возвращается код результата выполнения операции.
    public static function checkUpdate($tracker, $torrentInfo, $classType) {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
       if ($trackerClass == null){ // класс не найден
            return null;
        }
        else {
            if($classType == 'engine')
                $trackerClass::main($torrentInfo);
            else if ($classType == 'search')
                $trackerClass::mainSearch($torrentInfo);
        }
    }
    
    // функция выполняет генерацию URL ссылки для треккера
    // параметры:
    //    tracker     - имя треккера
    //    torrent_id  - идентификатор раздачи на треккере
    // результат:
    //    возвращается готовую ссылку на раздачу
    public static function generateURL($tracker, $torrent_id) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        $torrent_url = "";
        if ( $trackerClass != null && method_exists($trackerClass, 'generateURL') )
            $torrent_url = $trackerClass::generateURL($tracker, $torrent_id);
        
        return $torrent_url;
    }
    
    // функция проверки введёного URL`а
    public static function checkRule($tracker, $data) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        return $trackerClass::checkRule($data);
    }
    
    // функция возвращает имя треккера с учетом всех возможных ассоциаций
    // параметры:
    //    tracker - имя треккера
    public static function getTrackerName($tracker) {
        
        if( isset(Trackers::$associationList[$tracker]) && ! empty(Trackers::$associationList[$tracker]) )
            $tracker = Trackers::$associationList[$tracker];
        
        return $tracker;
    }
    
    // функция выделяет из ссылки идентификатор раздачи
    // параметры:
    //    tracker - имя треккера
    //    url - ссылка на раздачу
    // результат:
    //    возвращается идентификатор раздачи
    public static function getThreme($tracker, $url) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        if( method_exists($trackerClass, 'getThreme') )
            $threme = $trackerClass::getThreme($url);
        else{
            $url = parse_url($url);
            $query = explode('=', $url['query']);
            $threme = $query[1];
        }
        
        return $threme;
    }
    
    // функция получает спиок треккеров, которые соответствуют переданному типу
    // параметры:
    //    trackerType - тип искомых треккеров
    // результат:
    //    массив содержащий параметры треккеров
    public static function getTrackersByType($trackerType) {
        $result = array();
        foreach (Trackers::$trackersList as $trackerData) {
            if ($trackerData['type'] == $trackerType)
                $result[] = $trackerData;
        }
        return $result;
    }
}
?>