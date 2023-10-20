<?php
namespace eGamings\WLC;

/**
 * Class TablesInfo
 * @codeCoverageIgnore
 * @package eGamings\WLC
 */
class TablesInfo
{
    const PROVIDERS_TABLES_INFO_FUNDIST_URL = '/Providers/TablesInfo';

    public static function getTablesInfo(string $provider, string $lang): ?array
    {
        $system = System::getInstance();
        $url = self::PROVIDERS_TABLES_INFO_FUNDIST_URL;

        $transactionId = $system->getApiTID(self::PROVIDERS_TABLES_INFO_FUNDIST_URL);

        $hash = md5(trim(self::PROVIDERS_TABLES_INFO_FUNDIST_URL, '/') .'/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));

        $params = [
            'Provider' => $provider,
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

        $rawResponse = $system->runFundistAPI($url);

        $response = explode(',', $rawResponse, 2);

        $result = json_decode($response[1] ?? '', true);

        if ($response[0] != 1 || json_last_error() !== JSON_ERROR_NONE) {
            Logger::log(sprintf("Cannot fetch provider's hot events: %s", $rawResponse));
            return null;
        }

        return (array) $result;
    }
}
