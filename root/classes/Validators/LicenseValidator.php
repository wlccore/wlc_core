<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators;

use eGamings\WLC\Logger;
use eGamings\WLC\System;
use JsonException;

/**
 * @codeCoverageIgnore
 */
class LicenseValidator extends AbstractValidator
{
    private const CHECK_LICENSE_FIELDS_URL = '/User/CheckLicenseFields';

    /** @var System */
    private $system;

    public function __construct()
    {
        $this->system = System::getInstance();
    }

    public function validate($value, $params, $data, $field)
    {
        $fields = [];
        foreach ($this->checkedFields() as $fieldName => $fieldAlias) {
            if (empty($data[$fieldName])) {
                return false;
            }

            $fields[$fieldAlias] = $data[$fieldName];
        }

        return $this->request(self::CHECK_LICENSE_FIELDS_URL, $fields);
    }

    private function checkedFields(): array
    {
        return [
            'firstName' => 'Name',
            'lastName' => 'LastName',
            'countryCode' => 'Country',
            'city' => 'City',
            'address' => 'Address',
        ];
    }

    private function request(string $uri, array $params = []): bool
    {
        $transactionId = $this->system->getApiTID($uri);

        $hash = md5(implode('/', [
            trim($uri, '/'),
            '0.0.0.0',
            $transactionId,
            _cfg('fundistApiKey'),
            _cfg('fundistApiPass'),
        ]));

        $params = array_merge($params, [
            'TID' => $transactionId,
            'Hash' => $hash,
        ]);

        $response = $this->system->runFundistAPI($uri . '?&' . http_build_query($params));
        [$code, $data] = explode(',', $response, 2);

        if ((int)$code !== 1) {
            Logger::log('Error fetch data: ' . $response);

            return false;
        }

        try {
            $result = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Logger::log('Error fetch data: ' . $e->getMessage());

            return false;
        }

        return (bool)$result;
    }
}
