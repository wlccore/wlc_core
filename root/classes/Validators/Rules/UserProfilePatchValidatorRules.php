<?php

namespace eGamings\WLC\Validators\Rules;

/**
 * @class UserProfilePatchValidatorRules
 * @namespace eGamings\WLC\Validators\Rules
 * @extends UserProfileValidatorRules
 * @uses eGamings\WLC\User
 */
class UserProfilePatchValidatorRules extends UserProfileValidatorRules
{
    public function getValidateFields($data) {
        $fields = parent::getValidateFields($data);

        $fields['currentPassword']['validators']['required'] = false;
        $fields['repeat_password']['validators']['required'] = false;
        $fields['currency']['validators']['required'] = false;
        $fields['firstName']['validators']['required'] = false;
        $fields['lastName']['validators']['required'] = false;
        $fields['country']['validators']['required'] = false;

        return $fields;
    }
}
