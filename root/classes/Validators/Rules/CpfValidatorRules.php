<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators\Rules;

final class CpfValidatorRules extends AbstractValidatorRules
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
                    'required' => $data['countryCode'] === 'bra',
                    'cpf' => true,
                ],
                'errors' => [
                    'required' => _('CPF field is empty'),
                    'cpf' => _('Wrong CPF number format'),
                ],
            ],
        ];
    }
}
