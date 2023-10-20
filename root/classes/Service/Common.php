<?php

namespace eGamings\WLC\Service;

class Common
{
    /**
     * @param string $sort
     * @param array $map
     * @return array
     */
    public static function sortToOrderBy(string $sort, array $map = []): array
    {
        if (empty($sort)) {
            return [];
        }

        $columns = explode(',', $sort);
        $result = [];
        foreach ($columns as $column) {
            $direction = 'asc';
            if (strpos($column, '-') !== false) {
                $direction = 'desc';
                $column = substr($column, 1);
            }

            $result[] = ($map[$column] ?? $column) . ' ' . $direction;
        }

        return $result;
    }
}