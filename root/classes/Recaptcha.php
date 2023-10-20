<?php
namespace eGamings\WLC;

class Recaptcha
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var array
     */
    private $whiteList;

    /**
     * @var string
     */
    private $siteKey = '';

    /**
     * @var string
     */
    private $secretKey = '';

    public function __construct()
    {
        $this->enabled = _cfg('recaptcha');
        $this->siteKey = _cfg('recaptchaSiteKey');
        $this->secretKey = _cfg('recaptchaSecretKey');
        $this->whiteList = _cfg('recaptchaIPsWhiteList');

        $url = (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/www.google.com/recaptcha/api/siteverify';
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool)$this->enabled;
    }

    /**
     * @return array
     */
    public function whiteList(): array
    {
        if (empty($this->whiteList)) {
            return [];
        }

        return $this->whiteList;
    }

    /**
     * @var string $token reCAPTCHA token
     * @return bool
     */
    public function check(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $response = $this->sendRequest([
            'secret' => $this->secretKey,
            'response' => $token,
        ]);

        $score = (_cfg('recaptchaScore') && _cfg('recaptchaScore') >= 0) ? _cfg('recaptchaScore') : 0.5;

        return (isset($response['success']) && $response['success'] == true && $response['score'] > $score);
    }

    /**
     * @param array $data
     * @return array
     */
    private function sendRequest(array $data = []): array
    {
        $ch = curl_init();

        $data = http_build_query($data);

        $curlOptions = [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        ];

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        if (_cfg('recaptchaLog')) {
            error_log("XXX recaptcha " . print_r([$data, $response], true));
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        return ($errno === 0 && $code == 200) ? json_decode($response, true) : [];
    }
}
