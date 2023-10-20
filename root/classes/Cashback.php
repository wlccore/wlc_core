<?php

declare(strict_types=1);

namespace eGamings\WLC;

use eGamings\WLC\Traits\HasFuncoreRequests;

class Cashback
{
    use HasFuncoreRequests;

    private const CASHBACK_GET_FUNDIST_URL = '/Cashback/Get';
    private const CASHBACK_PAY_FUNDIST_URL = '/Cashback/Pay';

    /** @var System */
    private $system;

    public function __construct()
    {
        $this->system = System::getInstance();
    }

    public function getListForUser(object $user): ?array
    {
        return $this->request(self::CASHBACK_GET_FUNDIST_URL, [
            'Login' => $user->id,
            'Password' => $user->api_password,
        ]);
    }

    public function payForUser(object $user, int $cashbackId): ?array
    {
        return $this->request(self::CASHBACK_PAY_FUNDIST_URL, [
            'Login' => $user->id,
            'Password' => $user->api_password,
            'IDCashback' => $cashbackId,
        ]);
    }
}
