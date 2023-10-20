<?php
namespace eGamings\WLC\Validators\Rules;

use eGamings\WLC\User;

class UserProfilePartialValidatorRules extends UserProfileValidatorRules
{
    public function getValidateFields($data)
    {
        $fields = parent::getValidateFields($data);

    	$email_query = '';
    	if (User::isAuthenticated()) {
    	    $user = User::getInstance();
    	    $email_query .= ' AND id != ' . (int) $user->userData->id;
    	}

        if (!is_array($data)) {
            $data = ['Swift' => ''];
        }

        $fields += [
            'email' => [
                'validators' => [
                    'required' => true,
                    'mail' => true,
                    'unique' => [
                        'table' => 'users',
                        'query' => $email_query
                    ]
                ],
                'errors' => [
                    'required' => _('Email is required'),
                    'mail' => _('Email has invalid format'),
                    'unique' => _('Email already in use')
                ]
            ],
            'pre_phone' => [
                'validators' => [
                    'required' => true
                ],
                'errors' => [
                    'required' => _('Phone code is empty')
                ]
            ],
            'main_phone' => [
                'validators' => [
                    'required' => true,
                    'match' => '/^\d{6,12}(\-\d{0,6})?$/'
                ],
                'errors' => [
                    'required' => _('Phone number field is empty'),
                    'match' => _('Phone number invalid format')
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
            'idNumber' => [
                'validators' => [
                    'id-number' => _cfg('UniqueIDNumber')
                ],
                'errors' => [
                    'id-number' => _('ID Number is not unique')
                ]
            ],
        ];

        return $fields;
    }
}
