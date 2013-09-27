<?php
require_once 'ClientAdapter.interface.php';

class ClientAdapterFactory
{
    /**
     * @param string $name Название адаптера
     * @return ClientAdapter
     */
    public static function getStorage($name)
    {
        $class = sprintf('%sClient', ucfirst($name));
        $file = sprintf('%s/%s.class.php', dirname(__FILE__), $class);
        include_once $file;
        return new $class;
    }
}
?>