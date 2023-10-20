<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators\Rules;

/**
 * @codeCoverageIgnore
 */
class UserRegisterMaltaValidatorRules extends UserRegisterValidatorRules
{
    public function getValidateFields($data): array
    {
        $requiredFields = [
            'firstName' => 'First name',
            'lastName' => 'Last name',
            'birthYear' => 'Birth year',
            'birthMonth' => 'Birth month',
            'birthDay' => 'Birth day',
            'countryCode' => 'Country',
            'city' => 'City',
            'address' => 'Address',
        ];

        $fields = parent::getValidateFields($data);

        foreach ($requiredFields as $field => $fieldName) {
            $fields[$field] = $this->makeField($fieldName);
        }

        $fields['license'] = [
            'validators' => [
                'license' => true,
            ],
            'errors' => [
                'license' => _('An account with such personal details already exists'),
            ],
        ];

        return $fields;
    }

    private function makeField(string $fieldName): array
    {
        return [
            'validators' => [
                'required' => true,
            ],
            'errors' => [
                'required' => _("{$fieldName} is required"),
            ],
        ];
    }
}
