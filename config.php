<?php
class Config
{
    static $confArray;

    public static function read($name)
    {
        return self::$confArray[$name];
    }

    public static function write($name, $value)
    {
        self::$confArray[$name] = $value;
    }
}

Config::write('db.host', 'localhost');
Config::write('db.type', 'mysql');
Config::write('db.charset', 'utf8');
Config::write('db.port', '3306');
Config::write('db.basename', 'torrentmonitor');
Config::write('db.user', 'root');
Config::write('db.password', 'k1985p');
?>