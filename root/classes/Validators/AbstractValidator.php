<?php

namespace eGamings\WLC\Validators;

/**
 * @class AbstractValidator
 */
abstract class AbstractValidator
{
    /**
     * Validate value
     *
     * @param mixed $value
     * @param mixed $params
     * @param mixed[] $data
     * @param string $field
     * @return boolean
     */
    abstract public function validate($value, $params, $data, $field);
}
