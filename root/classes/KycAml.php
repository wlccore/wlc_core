<?php

declare(strict_types=1);

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Traits\HasFuncoreRequests;

class KycAml
{
    use HasFuncoreRequests;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    private const KYCAML_GET_URL = '/KycAml/GetUrl';
    private const KYCAML_GENERATE_URL = '/KycAml/GenerateUrl';
    private const KYCAML_AML_CHECK = '/KycAml/AmlCheck';
    private const KYCAML_AML_RESULT = '/KycAml/AmlResult';

    public function getUrlForUser(object $user): ?array
    {
        return $this->request(self::KYCAML_GET_URL, [
            'Login' => $user->id,
            'Password' => $user->api_password,
        ]);
    }

    public function generateUrlForUser(object $user): ?array
    {
        return $this->request(self::KYCAML_GENERATE_URL, [
            'Login' => $user->id,
            'Password' => $user->api_password,
        ]);
    }

    /**
     * @param array $data
     *
     * @return string|null
     *
     * @throws ApiException
     */
    public function amlCheck(array $data): ?string
    {
        $result = $this->request(self::KYCAML_AML_CHECK, $data,
            [$data['Email'], $data['Name'], $data['LastName'], $data['DateOfBirth']]);

        return $result['reference'] ?? null;
    }

    /**
     * @param object $user
     *
     * @return string|null
     *
     * @throws ApiException
     */
    public function amlResult(object $user): ?string
    {
        if (!$additional = json_decode($user->additional_fields, true)) {
            return null;
        }

        if (!$reference = $additional['aml_reference']) {
            return null;
        }

        $result = $this->request(self::KYCAML_AML_RESULT, [
            'Login' => $user->id,
            'Password' => $user->api_password,
            'Reference' => $reference,
        ]);

        return $result['status'] ?? null;
    }
}
