<?php

include_once dirname(__FILE__).'/Database.class.php';

abstract class Plugin
{
    // Возвращает имя класса плагина, для записи в БД
    public function Name()
    {
        return get_called_class();
    }

    // Должна возвращать тип плагина (Notifier наприер)
    public abstract function Type();

    // Должна возвращать наименование для отображение на сайте (человекопонятное)
    public abstract function VerboseName();

    // Должна возвращать подробное описание сервиса (например урл и суть)
    public abstract function Description();

    public function Group()
    {
        // Ф-ция изаначально задумана для возможности использования однотипных плагинов с
        // разными настройками (например один и тот же сервис нотификации но на 2 разных
        // аккаунта.). По дефолту, считаем, что плагин может быть использован только 1 раз.
        // Если нужно таки использовать несколько раз - реализуется в наследниках. (см нотификаторы)
        return "";
    }

    final public function GetProperty($setting)
    {
        return Database::getPluginSetting($this, $setting);
    }

    final public function SetProperty($setting, $value)
    {
        Database::setPluginSetting($this, $setting, $value);
    }

}
?>
