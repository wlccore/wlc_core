<?php

namespace eGamings\WLC\Validators\Rules;

class UserProfileIbanValidatorRules extends UserBaseValidatorRules
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
            ]
        ];
    }
}
