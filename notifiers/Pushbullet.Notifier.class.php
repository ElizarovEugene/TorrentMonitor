<?php

include_once dirname(__FILE__).'/Notifier.class.php';

class PushbulletNotifier extends Notifier
{
    protected function localSend($address, $date, $tracker, $message, $header_message, $name=0)
    {
        $msg = 'Дата: '.$date."\r\n".'Трекер: '.$tracker."\r\n".'Сообщение: '.$message."\r\n";
        $postData = array('type' => 'note',
                          'title' => $header_message,
                          'body' => $msg );

        $ch = curl_init('https://api.pushbullet.com/v2/pushes');
        curl_setopt_array($ch, array(CURLOPT_POST => TRUE,
                                     CURLOPT_RETURNTRANSFER => TRUE,
                                     CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$address,
                                                                 'Content-Type: application/json'),
                                     CURLOPT_POSTFIELDS => json_encode($postData) ));
        $response = curl_exec($ch);
        return 'Отправили уведомление в сервис Pushbullet на адрес "'.$address.'"<br />';
    }
}


?>
