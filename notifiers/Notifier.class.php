<?php

$dr = dirname(__FILE__);
include_once $dr.'/../class/Database.class.php';
foreach (glob($dr."/*.Notifier.class.php") as $file)
         include_once $file;

abstract class Notifier
{
    private static $errors;

    // Место для реализации непосредственной отправки для наследников.
    // Возвращает лог выполнения (HTML)
    protected abstract function localSend($address, $type, $date, $tracker, $message, $header_message, $name=0);

    private static function Create($notifierName)
    {
        $notifierClass = $notifierName."Notifier";
        if  (!class_exists($notifierClass))
            return null;

        return new $notifierClass();
    }


    public static function send($type, $date, $tracker, $message, $name=0)
    {
        $result = '';
        $send = Database::getSetting('send');
        if ($send)
        {
            if ($type == 'warning')
            {
                $header_message = 'Torrent Monitor. Предупреждение.';
                $sendService = Database::getSetting('sendWarningService');
                $sendAddress = Database::getSetting('sendWarningAddress');
            }
            if ($type == 'notification')
            {
                $header_message = 'Torrent Monitor. Обновление.';
                $sendService = Database::getSetting('sendUpdateService');
                $sendAddress = Database::getSetting('sendUpdateAddress');
            }

            $sendWarning = Database::getSetting('sendWarning');
            $sendUpdate =  Database::getSetting('sendUpdate');
            if ((($type == 'warning') and $sendWarning) or
                (($type == 'notification') and $sendUpdate))
            {
                if ( empty($sendService) || empty($sendAddress) )
                    return $result."Для уведомлений типа '".$type."' не задан сервис либо адрес для отправки.<br/>";

                $notifier = Notifier::Create($sendService);
                if ($notifier == null)
                    return $result."Class for ".$sendService." not found!<br/>";

                $success = $notifier->localSend($sendAddress, $type, $date, $tracker, $message, $header_message, $name);
                if ($success)
                {
                    $result .= 'Отправили уведомление на '.$sendService.' &lt;'.$sendAddress.'&gt;<br/>';
                    Database::clearWarnings($sendService);
                }
                else
                {
                    $result .= 'Ошибка при отправке уведомления на '.$sendService.' &lt;'.$sendAddress.'&gt;<br/>';
                    Errors::setWarnings($sendService, 'notif_fail');
                }
                $notifier = null;
            }
        }
        return $result;
    }

    public static function findWarning()
    {
        $trackersArray = Database::getTrackersList();
        foreach ($trackersArray as $tracker)
        {
            $warningsCount = Database::getWarningsCount($tracker);
            if ($warningsCount == 1)
            {
                $warningsArray = Database::getWarnings($tracker);
                Notifier::send('warning', $warningsArray['time'], $tracker, $warningsArray['reason']);
            }
        }
    }
}
?>
