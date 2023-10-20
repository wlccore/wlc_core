<?php

namespace eGamings\WLC\Validators\Rules;

class EtheriumSignatureValidatorRules extends UserBaseValidatorRules
{
    public function getValidateFields($data)
    {
        return [
            'message' => [
                'validators' => [
                    'required' => true,
                ],
                'errors' => [
                    'required' => _('message is required'),
                ]
            ],
            'walletAddress' => [
                'validators' => [
                    'required' => true,
                ],
                'errors' => [
                    'required' => _('address is required'),
                ]
            ],
            'signature' => [
                'validators' => [
                    'required' => true,
                    'etherium-signature' => true,
                ],
                'errors' => [
                    'required' => _('signature is required'),
                    'etherium-signature' => _('Wrong signature'),
                ]
            ],
        ];
    }
}