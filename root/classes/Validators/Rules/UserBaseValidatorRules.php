<?php

namespace eGamings\WLC\Validators\Rules;
use eGamings\WLC\Config;
use eGamings\WLC\User;
use eGamings\WLC\Api;
use eGamings\WLC\Validators\BirthValidator;

/**
 * @class UserBaseValidator
 * @namespace eGamings\WLC\RestApi\Validators
 * @extends AbstractValidator
 * @uses eGamings\WLC\User
 */
class UserBaseValidatorRules extends AbstractValidatorRules
{
    public function getValidateFields($data) {

        // email can be empty #9176
        $email_req = !( (_cfg('loginBy') === 'all' && empty($data['email'])) || _cfg('loginBy') === 'login' );

        $birthValidator = new BirthValidator();
        $age = $birthValidator->getCountryAgeBanNumber($data['countryCode'] ?? null);

        return [
            'email' => [
                'validators' => [
                    'required' => $email_req,
                    'mail' => $email_req
                ],
                'errors' => [
                    'required' => _('Email is required'),
                    'mail' => _('Email has invalid format'),
                ]
            ],
            'repeat_password' => [
                'field' => 'passwordRepeat',
                'validators' => [
                    'required' => true,
                    'equals' => 'password'
                ],
                'errors' => [
                    'required' => _('Repeat password field is empty'),
                    'equals' => _('Password does not match')
                ]
            ],
            'firstName' => [
                'validators' => [
                    'required' => false,
                    'name' => true
                ],
                'errors' => [
                    'required' => _('First name field is empty'),
                    'name' => _('First name must contain only letters')
                ]
            ],
            'lastName' => [
                'validators' => [
                    'required' => false,
                    'name' => true
                ],
                'errors' => [
                    'required' => _('Last name field is empty'),
                    'name' => _('Last name must contain only letters')
                ]
            ],
            'sex' => [
                'field' => 'gender',
                'validators' => [
                    'required' => false,
                    'range' => ['m', 'f']
                ],
                'errors' => [
                    'required' => _('Gender field is empty'),
                    'range' => _('Gender unallowed value')
                ]
            ],
            'currency' => [
                'validators' => [
                    'required' => true,
                    'range' => $this->getCurrencies(),
                ],
                'errors' => [
                    'required' => _('Currency field is empty'),
                    'range' => _('Currency field is invalid')
                ]
            ],
            'country' => [
                'field' => 'countryCode',
                'validators' => [
                    'required' => _cfg('countryRequired') ?: false,
                    'country' => true
                ],
                'errors' => [
                    'required' => _('Country code is empty'),
                    'country' => _('Country code is invalid')
                ]
            ],
            'state' => [
                'field' => 'stateCode',
                'validators' => [
                    'required' => false,
                    'state' => true
                ],
                'errors' => [
                    'required' => _('State code is empty'),
                    'state' => _('Invalid country state')
                ]
            ],
            'pre_phone' => [
                'field' => 'phoneCode',
                'validators' => [
                    'required' => false
                ],
                'errors' => [
                    'required' => _('Phone code is empty')
                ]
            ],
            'main_phone' => [
                'field' => 'phoneNumber',
                'validators' => [
                    'required' => false,
                    'match' => '/^\d{6,12}(\-\d{0,6})?$/'
                ],
                'errors' => [
                    'required' => _('Phone number field is empty'),
                    'match' => _('Phone number invalid format')
                ]
            ],
            'code' => [
                'field' => 'code',
                'validators' => [
                    'required' => false
                ],
                'errors' => [
                    'required' => _('Validation code is empty')
                ]
            ],
            'birthDateFormat' => [
                'validators' => [
                    'birthFormat' => false // Not strict validation
                ],
                'errors' => [
                    'birthFormat' => _('Invalid DateOfBirth format')
                ]
            ],
            'birthDate' => [
                'validators' => [
                    'birth' => false // Not strict validation
                ],
                'errors' => [
                    'birth' => sprintf(_('You are less than %d year old'), $age)
                ]
            ],
            'birthYear' => [
                'validators' => [
                    'min' => 1900
                ],
                'errors' => [
                    'min' => _('Date of birth must not be earlier than 1900-01-01')
                ]
            ],
        ];
    }

    public function validate($data, $fields = [])
    {
        $result = parent::validate($data, $fields);

        if (!$result['result'] && array_key_exists('currency', $result['errors']) && !empty($data['currency'])) {
            $user = new User();
            $user->logUserData('invalid currency', json_encode($data));
        }

        if (!empty($data['password']) &&
            (($data['password'] == $data['email'] && in_array(_cfg('loginBy'),['all','email']))
            ||
            (!empty($data['login']) && $data['password'] == $data['login'] && in_array(_cfg('loginBy'),['all','login']))
            ))
        {
            $result['result'] = false;
            $result['errors']['password'] = _('Password must not be equal to login');
        }

        return $result;
    }

    /**
     * @param bool $registration
     *
     * @return array
     */
    protected function getCurrencies(bool $registration = false): array
    {
        $currencies = [];
        $config = Config::getSiteConfig();
        if (is_array($config['currencies'])) {
            $currencies = array_map(function ($currency) use ($registration) {
                if ($registration) {
                    return $currency['registration'] ? $currency['Name'] : null;
                }

                return $currency['Name'];
            }, $config['currencies']);
        }

        if (Api::isApiCall()) {
            $exclude_currencies = _cfg('exclude_currencies');
            if (is_array($exclude_currencies)) foreach($exclude_currencies as $exclude_currency) {
                if (!in_array($exclude_currency, $currencies)) {
                    $currencies[] = $exclude_currency;
                }
            }
        }

        return $currencies;
    }
}
