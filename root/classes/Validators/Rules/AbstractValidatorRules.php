<?php

namespace eGamings\WLC\Validators\Rules;

/**
 * @class AbstractValidatorRules
 * @namespace eGamings\WLC\RestApi\Validators
 */
abstract class AbstractValidatorRules
{
    /**
     * Return validation rules
     *
     * @return array
     */
    abstract protected function getValidateFields($data);

    protected $fields = [];

    protected function dataProcess($data) 
    {
        $fields = $this->fields;
        $errors = [];

        if (count($fields)) {
            foreach ($fields as $field => $fieldParams) {
                foreach ($fieldParams['validators'] as $validator => $params) {
                    $fieldName = $errorName = !empty($fieldParams['field']) ? $fieldParams['field'] : $field;
                    $value = array_key_exists($field, $data) ? $data[$field] : '';
                    if ($field != $fieldName && empty($value)) {
                        $value = array_key_exists($fieldName, $data) ? $data[$fieldName] : '';
                    }

                    $validatorClassName = ucwords(str_replace('-', ' ', $validator));
                    $validatorClassName = str_replace(' ', '', $validatorClassName).'Validator';
                    $validatorClassPath = 'eGamings\WLC\Validators\\'.$validatorClassName;
                    $isValid = true;

                    if (is_callable($params)) {
                        $isValid = call_user_func($value, $params, $data, $field);
                    } else {
                        if (class_exists($validatorClassPath)) {
                            $obValidator = new $validatorClassPath();
                            $isValid = $obValidator->validate($value, $params, $data, $field);
                        }
                    }

                    if (!$isValid) {
                        $errors[$errorName] = $fieldParams['errors'][$validator];
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    public function validate($data, $fields = [])
    {
        $this->fields = $this->getValidateFields($data);

        $response = [
            'result' => false,
            'errors'  => []
        ];

        if ($fields === null) {
            $fields = [];
        }

        if (!$this->checkInputParams($data, $fields)) {
            $response['errors']['$service'] = '$data or $fields parameter is missing or is not in array format.';
            return $response;
        }

        $errors = $this->dataProcess($data);

        // class should return array as result
        if(!is_array($errors)) {
            $response['errors']['$service'] = '$this->dataProcess should return result as array';
            return $response;
        }

        // if fields are set - return results only for matching keys
        if(!empty($fields)) {
            foreach($fields as $key => $field) {
                if (!empty($errors[$field])) {
                    $response['errors'][$field] = $errors[$field];
                }
            }
        } else {
            $response['errors'] = $errors;
        }

        $response['result'] = empty($response['errors']);

        return $response;
    }

    public function checkInputParams($data, $fields)
    {
        // check if data is assoc array
        $success = (is_array($data) && count(array_filter(array_keys($data), 'is_string')) === count(array_keys($data)));

        // check if fields is not assoc array (if provided)
        if ($success && !empty($fields)) {
            $success = (is_array($fields) && count(array_filter(array_keys($fields), 'is_string')) === 0);
        }

        return $success;
    }

}
