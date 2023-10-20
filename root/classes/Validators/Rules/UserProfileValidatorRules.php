<?php

namespace eGamings\WLC\Validators\Rules;

use eGamings\WLC\User;

/**
 * @class UserProfileValidator
 * @namespace eGamings\WLC\RestApi\Validators
 * @extends AbstractValidator
 * @uses eGamings\WLC\User
 */
class UserProfileValidatorRules extends UserBaseValidatorRules
{
    const LOWEST_PASSWORD_REGEXP = '/^[A-Za-z0-9!@#$%^&*()_+=-`~\]\\\[{}|\'\-;:\/.,\?\>\<]{5,}$/';
    public function getValidateFields($data) {
        $fields = parent::getValidateFields($data);

        $fields['repeat_password']['validators']['required'] = false;
        $fields['firstName']['validators']['required'] = true;
        $fields['lastName']['validators']['required'] = true;
        $fields['country']['validators']['required'] = true;

        $fields['currentPassword'] = [
            'field' => 'currentPassword',
            'validators' => [
                'required' => User::getInstance()->checkPassOnUpdate()
            ],
            'errors' => [
                'required' => _('Password field is empty')
            ]
        ];

        if (_cfg('PasswordSecureLevel') === 'custom:lowest') {
            $passwordMatch = self::LOWEST_PASSWORD_REGEXP;
            $passwordMin = 5;
        } else {
            $passwordMatch = '/^[A-z0-9[:punct:]]+$/i';
            $passwordMin = 6;
        }

        $fields['password'] = [
            'validators' => [
                'required' => false,
                'size' => $passwordMin,
                'match' => $passwordMatch,
                'password-level' => true,
                'equals' => 'newPasswordRepeat'
            ],
            'errors' => [
                'required' => _('New password field is empty'),
                'size' => _('New password must be at least 6 characters long'),
                'match' => _('New password may contain only latin letters, numbers and special symbols'),
                'password-level' => _('New password strength is weak'),
                'equals' => _('Password does not match')
            ]
        ];

        if (_cfg('PasswordSecureLevel') === 'custom:lowest') {
            $fields['password']['validators']['size'] = 5;
            $fields['password']['validators']['match'] = self::LOWEST_PASSWORD_REGEXP;
            $fields['password']['errors']['size'] = _('Password must be at least 5 characters long');
            $fields['password']['errors']['password-level'] = _('Password strength is weak');
        }

        $fields['repeat_password'] = [
            'field' => 'newPasswordRepeat',
            'validators' => [
                'required' => false,
                'equals' => 'password'
            ],
            'errors' => [
                'required' => _('Repeat password field is empty'),
                'equals' => _('Password does not match')
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

        return $fields;
    }
}
