<?php
namespace eGamings\WLC;

class Paycryptos
{
    /** @var Paycryptos|null  */
    private static $instance = null;

    /**
     * @return Paycryptos|null
     */
    public static function getInstance(): ?Paycryptos
    {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $path
     * @param bool $post
     * @param array $params
     * @return bool|string
     */
    public function send(string $path, bool $post, array $params = []): ?string
    {
        $url = $this->prepareUrl($path);

        $ch = empty($_SERVER['TEST_RUN']) ? curl_init() : true;

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => $post,
        ];

        if ($post) {
            $curlOptions['CURLOPT_POSTFIELDS'] = http_build_query($params);
        }

        empty($_SERVER['TEST_RUN']) ? curl_setopt_array($ch, $curlOptions) : true;
        $response = empty($_SERVER['TEST_RUN']) ? curl_exec($ch) : '1,success';
        empty($_SERVER['TEST_RUN']) ? curl_close($ch) : true;

        return $response;
    }

    /**
     * @param string $path
     * @return string
     */
    private function prepareUrl(string $path): string
    {
        return (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . _cfg('paycryptosUrl') . '/public/v1/' . $path;
    }
}