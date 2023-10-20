<?php

namespace eGamings\WLC\Validators\Rules;

use eGamings\WLC\Validators\BirthValidator;

/**
 * @class UserRegisterValidator
 * @namespace eGamings\WLC\RestApi\Validators
 * @extends AbstractValidator
 * @uses eGamings\WLC\User
 */
class UserRegisterValidatorRules extends UserBaseValidatorRules
{
    public function getValidateFields($data) {
        $fields = parent::getValidateFields($data);
        $email_req = !( (_cfg('loginBy') === 'all' && empty($data['email'])) || _cfg('loginBy') === 'login' );
        $fields['password'] = [
            'validators' => [
                'required' => true,
                'size' => 6,
                'match' => '/^[A-z0-9[:punct:]]+$/i',
                'password-level' => true
            ],
            'errors' => [
                'required' => _('Password field is empty'),
                'size' => _('Password must be at least 6 characters long'),
                'match' => _('Password may contain only latin letters, numbers and special symbols'),
                'password-level' => _('Password strength is weak')
            ]
        ];

        if (_cfg('PasswordSecureLevel') === 'custom:lowest') {
            $fields['password']['validators']['size'] = 5;
            $fields['password']['validators']['match'] = '/^[A-Za-z0-9!@#$%^&*()_+=-`~\]\\\[{}|\';:\/.,\?\>\<]{5,}$/';
            $fields['password']['errors']['size'] = _('Password must be at least 5 characters long');
            $fields['password']['errors']['password-level'] = _('Password strength is weak');
        } else if (_cfg('PasswordSecureLevel') === 'unsecure') {
            unset($fields['password']);
        }

        $birthValidator = new BirthValidator();
        $age = $birthValidator->getCountryAgeBanNumber($data['country'] ?? $data['countryCode'] ?? null);


        $fields['login'] = [
            'validators' => [
                'required' => ($email_req && empty($data['login'])) ? false : true,
                'unique' => ['table' => 'users']
            ],
            'errors' => [
                'required' => _('Login field is empty'),
                'unique' => _('Login is already registered')
            ]
        ];

        $fields['email'] = [
            'validators' => [
                'required' => $email_req,
                'mail' => $email_req,
                'unique' => !_cfg('hideEmailExistence') && $email_req ? ['table' => 'users'] : []
            ],
            'errors' => [
                'required' => _('Email is required'),
                'mail' => _('Email has invalid format'),
                'unique' => _('Email is already registered')
            ]
        ];

        if ((_cfg('userProfilePhoneIsRequired') || !empty($data['phoneNumber'])) && _cfg('registerUniquePhone')) {
            $fields['main_phone']['validators']['uniquephone'] = true;
            $fields['main_phone']['errors']['uniquephone'] = _('Phone is already registered');
        }

        $fields['birthDateFormat'] = [
            'validators' => [
                'birthFormat' => false // Not strict validation
            ],
            'errors' => [
                'birthFormat' => _('Invalid DateOfBirth format')
            ]
        ];

        $fields['birthDate'] = [
            'validators' => [
                'birth' => false // Not strict validation
            ],
            'errors' => [
                'birth' => sprintf(_('You are less than %d year old'), $age)
            ]
        ];

        $fields['IDNumber'] = [
            'field' => 'idNumber',
            'validators' => [
                'id-number' => _cfg('UniqueIDNumber')
            ],
            'errors' => [
                'id-number' => _('ID Number is not unique')
            ]
        ];

        $fields['currency']['validators']['range'] = $this->getCurrencies(true);

        return $fields;
    }
}
