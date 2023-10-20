<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\Db;

class UniqueValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($params['table']) || empty($value)) {
            return true;
        }

        $table = $params['table'];
        $field = !empty($params['field']) ? $params['field'] : $field;
        $value = Db::escape($value);

        $result = Db::fetchRow("
            SELECT id 
            FROM `{$table}` 
            WHERE 
              `{$field}` = '{$value}'".
              (!empty($params['query']) ? ' ' . $params['query'] : '')
        );

        if ($result === false) {
            return true;
        }

        return false;
    }
}
