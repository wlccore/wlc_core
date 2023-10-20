<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\States;

/**
 * Class StateValidator
 * @package eGamings\WLC\Validators
 */
class StateValidator
{
    /**
     * @param $value
     * @param $params
     * @param $data
     * @param $field
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        if (empty($data['countryCode'])) {
            return false;
        }

        $states = States::getStatesList('asc', _cfg('language'));
        
        $stateList = []; 
        if(!empty($states[$data['countryCode']])) {
            $stateList = array_column($states[$data['countryCode']],'value');
        } 

        return (is_array($states) && !empty($states)) ? in_array($value, $stateList) : false;
    }
}
