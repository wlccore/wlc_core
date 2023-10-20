<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators\Rules;

final class CnpValidatorRules extends AbstractValidatorRules
{
    /**
     * @param array $data
     *
     * @return array
     */
    protected function getValidateFields($data): array
    {
        return [
            'countryCode' => [
                'validators' => [
                    'required' => true,
                ],
                'errors' => [
                    'required' => _('Country is required'),
                ],
            ],

            'cpf' => [
                'validators' => [
                    'required' => $data['countryCode'] === 'rou',
                    'cnp' => true,
                ],
                'errors' => [
                    'required' => _('CNP field is empty'),
                    'cnp' => _('Wrong CNP number format'),
                ],
            ],
        ];
    }
}
