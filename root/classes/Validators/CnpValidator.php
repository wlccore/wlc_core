<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators;

class CnpValidator extends AbstractValidator
{
    /**
     * @param string $value
     * @param bool $params
     * @param array $data
     * @param string $field
     *
     * @return bool
     */
    public function validate($value, $params, $data, $field): bool
    {
        if (!$data || empty($value)) {
            return true;
        }

        if (!preg_match('/^(\d{13})$/', $value)) {
            return false;
        }
        
        $cnp = array_map("intval", str_split($value));
        $hashTable = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $hashResult = 0;
        for ($i=0; $i<12; $i++) {
            $hashResult += $cnp[$i] * $hashTable[$i];
        }

        $hashResult = $hashResult % 11;
        if ($hashResult == 10) {
            $hashResult = 1;
        }

        $year = ($cnp[1] * 10) + $cnp[2];
        switch ($cnp[0]) {
            case 1 : 
            case 2 : { 
                $year += 1900;
                break;  
            }
            case 3 : 
            case 4 : { 
                $year += 1800;
                break;
            } 
            case 5 : 
            case 6 : { 
                $year += 2000;
                break; 
            } 
            case 7 : 
            case 8 : 
            case 9 : {                
                $year += 2000;
                if ($year > (int)date('Y')-14) {
                    $year -= 100;
                }
                break;
            } 

            default : {
                return false;
            }
        }
        
        if (((int)date('Y') - $year) < 18) {
            return false;
        }

        if (
            !empty($data['birthYear'])
            && ($data['birthYear'] % 100 != ($cnp[1] * 10) + $cnp[2])
        ) {
            return false;
        }

        if (
            !empty($data['birthMonth'])
            && ((int)$data['birthMonth'] != ($cnp[3] * 10) + $cnp[4])
        ) {
            return false;
        }

        if (!empty($data['gender'])) {
            if ($data['gender'] == 'f' && in_array($cnp[0], [1, 3, 5])) {
                return false;
            }
            
            if ($data['gender'] == 'm' && in_array($cnp[0], [2, 4, 6])) {
                return false;
            }
        }

        return $cnp[12] == $hashResult;
    }
}
