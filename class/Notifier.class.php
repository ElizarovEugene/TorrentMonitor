<?php

$dr = dirname(__FILE__);
include_once $dr.'/Plugin.class.php';
include_once $dr.'/Database.class.php';


abstract class Notifier extends Plugin
{
    public static $type='Notifier';
    //private static $errors;
    private $_group="";

    // Место для реализации непосредственной отправки для наследников.
    // Возвращает bool результат выполнения
    protected abstract function localSend($type, $date, $tracker, $message, $header_message, $name=0);

    final public function Name()
    {
        return str_replace(Notifier::$type, "", get_called_class());
    }

    final public function Type()
    {
        return Notifier::$type;
    }

    final public function Group()
    {
        return $this->_group;
    }

    final public function SendUpdate()
    {
        $value = $this->GetProperty('sendUpdate');
        return (($value == '1') || ($value == 'true'));
    }

    final public function SendWarning()
    {
        $value = $this->GetProperty('sendWarning');
        return (($value == '1') || ($value == 'true'));
    }

    final public function SendAddress()
    {
        return $this->GetProperty('sendAddress');
    }

    final public function SetParams($address, $sendUpdate, $sendWarning)
    {
        $this->SetProperty('sendAddress', $address);
        $this->SetProperty('sendUpdate', $sendUpdate);
        $this->SetProperty('sendWarning', $sendWarning);
    }

    public static function Create($notifierName, $group="")
    {
        foreach (glob(dirname(__FILE__)."/../notifiers/*.Notifier.class.php") as $file)
            include_once $file;

        $notifierClass = $notifierName.Notifier::$type;
        if  (!class_exists($notifierClass))
            return null;

        $notifier = new $notifierClass();
        $notifier->_group = $group;

        return $notifier;
    }


    public static function send($type, $date, $tracker, $message, $name=0)
    {
        $result = '';

        if ($type == 'warning')
        {
            $header_message = 'Torrent Monitor. Предупреждение.';
        }
        else
        if ($type == 'notification')
        {
            $header_message = 'Torrent Monitor. Обновление.';
        }

        foreach (Database::getActivePluginsByType(Notifier::$type) as $plugin)
        {
            $notifier = Notifier::Create($plugin['name'], $plugin['group']);
            if ($notifier == null)
            {
                $result .= "Class for ".$plugin['name']." not found!<br/>";
                continue;
            }

            if ((($type == 'warning') and $notifier->SendWarning()) or
                (($type == 'notification') and $notifier->SendUpdate()))
            {
                $sendAddress = $notifier->SendAddress();
                if ( empty($sendAddress) )
                {
                    $result .= "Для уведомлений на сервис '.$notifier->VerboseName().' не задан адрес для отправки.<br/>";
                    continue;
                }

                $info = $notifier->localSend($type, $date, $tracker, $message, $header_message, $name);
                if ($info['success'])
                {
                    $result .= 'Успешно отправили уведомление на '.$notifier->VerboseName().' &lt;'.$notifier->SendAddress().'&gt;<br/>';
                    Database::clearWarnings($notifier->VerboseName().'_'.$notifier->Group());
                }
                else
                {
                    $result .= 'Ошибка при отправке уведомления на '.$notifier->VerboseName().' &lt;'.$notifier->SendAddress().'&gt;'.
                        '<p class="test-error">'.$info['response'].'</p>';
                    Errors::setWarnings($notifier->VerboseName().'_'.$notifier->Group(), 'notif_fail');
                }
            }
            $notifier = null;
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
