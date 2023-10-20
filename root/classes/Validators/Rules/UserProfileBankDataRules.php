<?php

namespace eGamings\WLC\Validators\Rules;

class UserProfileBankDataRules extends UserBaseValidatorRules
{
    public function getValidateFields($data)
    {
        return [
            'ibanNumber' => [
                'validators' => [
                    'iban' => $data['Iban']
                ],
                'errors' => [
                    'iban' => _('Wrong IBAN number')
                ]
            ],
            'swift' => [
                'validators' => [
                    'swift' => $data['Swift']
                ],
                'errors' => [
                    'swift' => _('Wrong SWIFT number')
                ]
            ],
        ];
    }
}