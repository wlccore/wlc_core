<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Provider\IUser;

class UserMock implements IUser
{
    public $user = null;
    public $userData = null;

    public function isUser($check_user_object = true): bool
    {
        return $this->user !== null;
    }

    /**
     * @return null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null $user
     */
    public function setUser($user): IUser
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return null
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param null $userData
     */
    public function setUserData($userData): void
    {
        $this->userData = $userData;
    }
}