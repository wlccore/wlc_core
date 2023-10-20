<?php
namespace eGamings\WLC\Validators;

/**
 * Class RangeValidator
 * @package eGamings\WLC\Validators
 */
class RangeValidator
{
    /**
     * @param $value
     * @param $params
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        if (!is_array($params) || empty($params)) {
            return true;
        }

        return in_array($value, $params);
    }
}
