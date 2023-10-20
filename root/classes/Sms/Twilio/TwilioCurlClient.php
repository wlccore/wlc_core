<?php

declare(strict_types=1);

namespace eGamings\WLC\Sms\Twilio;

use Twilio\Http\CurlClient;

final class TwilioCurlClient extends CurlClient
{
    public function request(
        $method,
        $url,
        $params = [],
        $data = [],
        $headers = [],
        $user = null,
        $password = null,
        $timeout = null
    ) {
        if (defined('KEEPALIVE_PROXY')) {
            $urlReplaces = [
                'https://api.twilio.com' => KEEPALIVE_PROXY . '/api.twilio.com',
                'https://api.twilio.com:8443' => KEEPALIVE_PROXY . '/api_twilio_httpalt',
            ];

            $url = str_replace(array_keys($urlReplaces), array_values($urlReplaces), $url);
        }

        return parent::request(
            $method,
            $url,
            $params,
            $data,
            $headers,
            $user,
            $password,
            $timeout,
        );
    }
}
