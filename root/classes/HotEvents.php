<?php
namespace eGamings\WLC;

class HotEvents {
    const PROVIDERS_HOTEVENTS_FUNDIST_URL = '/Providers/HotEvents';

    public static function getEvents(string $providerName, string $lang): ?array {
        $system = System::getInstance();
        $url = self::PROVIDERS_HOTEVENTS_FUNDIST_URL;

        $transactionId = $system->getApiTID(self::PROVIDERS_HOTEVENTS_FUNDIST_URL);

        $hash = md5(trim(self::PROVIDERS_HOTEVENTS_FUNDIST_URL, '/') .'/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));

        $params = [
            'Provider' => $providerName,
            'lang' => $lang,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
        ];

        $user = User::getInstance();

        if ($user->isAuthenticated() && $user->userData->currency) {
            $params['currency'] = $user->userData->currency;
        }

        $url .= '?&' . http_build_query($params);

        $response = $system->runFundistAPI($url);

        $brakedown = explode(',', $response, 2);

        $result = json_decode($brakedown[1] ?? '', true);

        if ($brakedown[0] != 1 || json_last_error() !== JSON_ERROR_NONE) {
            Logger::log("Cannot fetch provider's hot events: " . $response);
            return null;
        }

        return (array) $result;
    }
}
