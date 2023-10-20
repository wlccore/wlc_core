<?php

namespace eGamings\WLC;


/**
 * @brief Acquire a global system-wide lock by name
 */
class GlobalLock
{
    private $redis;
    private $name;
    private $ttl;
    private $lock_handle = null;
    private $except_on_fail;

    /**
     * @param $name string Unique lock name
     * @param $ttl_ms int Time-To-Live of acquired lock in milliseconds (it is seen as released after that)
     * @param $except_on_fail bool if true, throw exception on failed acquisition
     *
     * @throws \Error
     */
    public function __construct($name, $ttl_ms, $except_on_fail = true)
    {
        $this->except_on_fail = $except_on_fail;

        $redis = System::redis();
        $random = bin2hex(openssl_random_pseudo_bytes(8));

        # #2307 - GlobalLock: sometimes lock is left with no TTL
        if ((int)$redis->pttl($name) == -1) {
            // Delete has produced weird results. Try re-enforce TTL again
            $redis->pexpire($name, $ttl_ms);
        }

        if ($redis->set($name, $random, ['NX', 'PX' => $ttl_ms])) {
            $this->redis = $redis;
            $this->name = $name;
            $this->lock_handle = $random;
            $this->ttl = $ttl_ms;

            register_shutdown_function(function () {
                $this->release();
            });
        } elseif ($this->except_on_fail) {
            throw new \Error("GlobalLock failed ($name)");
        }
    }

    /**
     * @brief Check if lock was acquired
     */
    public function isLocked()
    {
        if ($this->lock_handle) {
            return ($this->redis->get($this->name) === $this->lock_handle);
        }

        return false;
    }

    /**
     * @brief Reset lock timeout
     * @throw Exception on failure
     */
    public function refresh()
    {
        $res = 'Not Locked';

        if ($this->lock_handle) {
            $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("PEXPIRE", KEYS[1], ARGV[2] )
            else
                return 0
            end
            ';

            $res = $this->redis->eval($script, [$this->name, $this->lock_handle, $this->ttl], 1);

            if ($res) {
                return true;
            }

            $this->lock_handle = null;
        }

        if ($this->except_on_fail) {
            throw new \Error("GlobalLock failed ({$this->name}, $res)");
        }

        return false;
    }

    /**
     * @brief Release previously acquired lock
     */
    public function release()
    {
        if ($this->lock_handle) {
            $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
            ';
            $this->redis->eval($script, [$this->name, $this->lock_handle], 1);
            $this->lock_handle = null;
        }
    }

    /**
     * @brief Auto-cleanup
     */
    public function __destruct()
    {
        $this->release();
    }
}
