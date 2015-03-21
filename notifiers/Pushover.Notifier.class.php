<?php

include_once dirname(__FILE__).'/../class/Notifier.class.php';
include_once dirname(__FILE__).'/../class/Errors.class.php';

class PushoverNotifier extends Notifier
{
    protected function localSend($address, $type, $date, $tracker, $message, $header_message, $name=0)
    {
        if ($type == 'notification')
            $priority = 0;
        if ($type == 'warning')
            $priority = 1;

        $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        $postfields = 'token=a9784KuYUoUdT4z47BassBLxWQGqFV&user='.$address.'&message='.$msg.'&title='.$header_message.'&priority='.$priority;
        $response = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'header'         => 1,
                'returntransfer' => 1,
                'url'            => 'https://api.pushover.net/1/messages.json',
                'postfields'     => $postfields,
            )
        );
        curl_close($ch);
        return preg_match('/\"status\":1/', $response);
    }
}


?>
