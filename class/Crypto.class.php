<?php
class Crypto
{
    const PREFIX = 'enc:';
    const CIPHER = 'aes-256-gcm';

    private static function getKey()
    {
        $hex = Config::read('encryption.key');
        if (empty($hex))
            return NULL;
        return hex2bin($hex);
    }

    public static function isEncrypted($value)
    {
        return is_string($value) && strpos($value, self::PREFIX) === 0;
    }

    //Шифрует значение, если в config.php задан encryption.key.
    //Если ключ не задан (config.php перенесён со старой версии без него),
    //значение возвращается как есть — приложение продолжает работать
    //в режиме без шифрования, как и раньше.
    public static function encrypt($plaintext)
    {
        if ($plaintext === NULL || $plaintext === '')
            return $plaintext;
        $key = self::getKey();
        if ($key === NULL)
            return $plaintext;
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === FALSE)
            return $plaintext;
        return self::PREFIX.base64_encode($iv.$tag.$ciphertext);
    }

    //Расшифровывает значение. Значения без префикса считаются перенесёнными
    //из старой версии (до появления шифрования) и возвращаются как есть —
    //это и есть переходный механизм при переносе старой базы.
    public static function decrypt($value)
    {
        if ( ! self::isEncrypted($value))
            return $value;
        $key = self::getKey();
        if ($key === NULL)
            return $value;
        $raw = base64_decode(substr($value, strlen(self::PREFIX)));
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($raw, 0, $ivLen);
        $tag = substr($raw, $ivLen, 16);
        $ciphertext = substr($raw, $ivLen + 16);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plaintext === FALSE ? '' : $plaintext;
    }
}
?>
