<?php
namespace eGamings\WLC\Storage;

/**
 * Interface IStorage
 * @package eGamings\WLC\Storage
 */
interface IStorage
{
    /**
     * Return value from storage by key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Save value in storage
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value);

    /**
     * Removes the value at the key
     *
     * @param string $key
     * @return mixed
     */
    public function remove($key);

    /**
     * Exists whether the value in the storage
     *
     * @param string $key
     * @return mixed
     */
    public function has($key);
}