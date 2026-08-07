<?php
//Класс для параллельного выполнения HTTP-запросов через curl_multi с дедупликацией одинаковых запросов
class CurlMultiFetcher
{
    private $handles = array();
    private $aliases = array();
    private $requestMeta = array();
    private $defaultOptions = array();

    public function __construct()
    {
        $this->defaultOptions = array(
            CURLOPT_USERAGENT         => Database::getSetting('userAgent'),
            CURLOPT_TIMEOUT           => Database::getSetting('httpTimeout'),
            CURLOPT_RETURNTRANSFER    => 1,
            CURLOPT_DNS_CACHE_TIMEOUT => 0,
        );
    }

    //регистрируем запрос; если такой же url+options уже зарегистрирован — добавляем $id как алиас существующего запроса
    public function add($id, $url, array $curlOptions = array())
    {
        $key = md5($url.serialize($curlOptions));

        if (isset($this->handles[$key]))
        {
            $this->aliases[$key][] = $id;
            return;
        }

        $options = $this->defaultOptions;
        $options[CURLOPT_URL] = $url;
        foreach ($curlOptions as $opt => $val)
            $options[$opt] = $val;

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $this->handles[$key] = $ch;
        $this->aliases[$key] = array($id);
        $this->requestMeta[$key] = array(
            'url'    => $url,
            'cookie' => isset($curlOptions[CURLOPT_COOKIE]) ? $curlOptions[CURLOPT_COOKIE] : '',
        );
    }

    //выполняем все зарегистрированные запросы параллельно, возвращаем $id => ['body'=>..., 'http_code'=>..., 'error'=>...]
    public function execute()
    {
        $results = array();

        if (empty($this->handles))
            return $results;

        $mh = curl_multi_init();
        foreach ($this->handles as $ch)
            curl_multi_add_handle($mh, $ch);

        $running = NULL;
        do
        {
            $status = curl_multi_exec($mh, $running);
            if ($running)
                curl_multi_select($mh);
        }
        while ($running && $status == CURLM_OK);

        foreach ($this->handles as $key => $ch)
        {
            $body     = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);

            if (($httpCode == 403 || $httpCode == 503) && !empty($body) && Sys::isCloudflarePage($body))
            {
                $meta     = $this->requestMeta[$key];
                $fsResult = Sys::getViaFlareSolverr($meta['url'], $meta['cookie']);
                if ($fsResult !== null)
                {
                    $body     = $fsResult['body'];
                    $httpCode = $fsResult['status'];
                    $error    = '';
                }
            }

            foreach ($this->aliases[$key] as $id)
            {
                $results[$id] = array(
                    'body'      => $body,
                    'http_code' => $httpCode,
                    'error'     => $error,
                );
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        $this->handles     = array();
        $this->aliases     = array();
        $this->requestMeta = array();

        return $results;
    }
}
?>
