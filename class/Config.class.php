<?php
class Config
{
    static $confArray;
    
    public static function extended($config = NULL)
    {
        Config::write('ext_proxy', '');
        if ( ! isset($config) || empty($config))
            $config = dirname(__FILE__).'/../'.'config.xml';
            
        Config::write('ext_filename', $config);

        if (file_exists($config))
        {
            $xml = simplexml_load_file($config);
            if ( ! empty($xml))
            {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                foreach ($array as $key => $val)
                {
                    Config::write('ext_'.$key, $val);
                }
            }
        }
    }
    
    public static function read($name)
    {
        return self::$confArray[$name];
    }

    public static function write($name, $value)
    {
        self::$confArray[$name] = $value;
    }
}
?>