<?php

namespace eGamings\WLC;


/**
 * Class Storage
 * @package eGamings\WLC
 */
class Storage extends System
{

    /**
     * Fetch record or records by user id and key from database
     *
     * @param $user_id
     * @param $data_key
     * @return array
     */
    public function getStorageData($user_id, $data_key)
    {
        $storageData = Db::fetchRows('SELECT `data_key`, `data_value`, `udate` ' .
            'FROM users_storage FORCE INDEX (`user_data_key`) ' .
            'WHERE `user_id` ' . $this->getUserCondition($user_id) . ' ' .
            $this->getKeyCondition($data_key) .
            'ORDER BY user_id DESC ' . $this->isOneRecord($data_key)
        );

        $result = [];
        if($storageData) {
            foreach ($storageData as $storageRecord) {
                if (!isset($result[$storageRecord->data_key])) {
                    $result[$storageRecord->data_key] = json_decode($storageRecord->data_value, true);
                }
            }
        }

        return $result;
    }

    /**
     * Delete current user storage record from database by user_id and key
     *
     * @param $user_id
     * @param $data_key
     * @return array
     */
    public function deleteStorageRecord($user_id, $data_key)
    {
        $deletedRecord = $this->getStorageData($user_id, $data_key);

        Db::query(
            'DELETE FROM `users_storage` ' .
            'WHERE `user_id` = "' . (int)$user_id . '" '.
            'AND `data_key` = "' . Db::escape($data_key) . '" ' .
            'LIMIT 1 '
        );

        return $deletedRecord;
    }

    /**
     * User condition
     *
     * @param $user_id
     * @return string
     */
    private function getUserCondition($user_id)
    {
        return (int)$user_id > 0 ? ' IN( "0","' . (int)$user_id . '" ) ' : ' = "0" ';
    }

    /**
     * Key condition
     *
     * @param $data_key
     * @return string
     */
    private function getKeyCondition($data_key)
    {
        return !empty($data_key) ? ' AND data_key = "' . DB::escape($data_key) . '" ' : '';
    }

    /**
     * Limit records condition
     *
     * @param $data_key
     * @return string
     */
    private function isOneRecord($data_key)
    {
        return !empty($data_key) ? ' LIMIT 1' : '';
    }

    /**
     * Create or update storage record
     *
     * @param $user_id
     * @param $data
     * @return array
     */
    public function setRecord($user_id, $data){

        $record = [];

        $result = Db::query(
            'INSERT INTO `users_storage` (`user_id`, `data_key`, `data_value`) ' .
            'VALUES ("' . (int)$user_id . '", ' .
            '"' . DB::escape($data['key']) . '", ' .
            '"' . DB::escape($data['value']) . '") ' .
            'ON DUPLICATE KEY UPDATE `data_value` = "' . DB::escape($data['value']) . '" '
        );

        if($result){
            $record = $this->getStorageData($user_id, $data['key']);
        }

        return $record;
    }

}