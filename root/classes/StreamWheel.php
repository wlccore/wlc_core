<?php

declare(strict_types=1);

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Traits\HasFuncoreRequests;

final class StreamWheel
{
    use HasFuncoreRequests;

    private const ADD_FUNDIST_URL = '/StreamWheels/Add';
    private const GET_FUNDIST_URL = '/StreamWheels/Get';
    private const JOIN_FUNDIST_URL = '/StreamWheels/Join';
    private const CANCEL_FUNDIST_URL = '/StreamWheels/Cancel';
    private const FINISH_FUNDIST_URL = '/StreamWheels/Finish';

    /**
     * @param object $user
     * @param array $data
     *
     * @throws ApiException
     *
     * @return int
     */
    public function add(object $user, array $data): int
    {
        $hashParams = [
            (int)$user->id,
            $data['Amount'],
            $data['Duration'],
            $data['WinnersCount'],
        ];

        return $this->request(self::ADD_FUNDIST_URL, array_merge([
            'Login' => (int)$user->id,
        ], $data), $hashParams);
    }

    /**
     * @param object $user
     * @param int $id
     *
     * @throws ApiException
     *
     * @return array
     */
    public function get(object $user, int $id): array
    {
        return $this->request(self::GET_FUNDIST_URL, [
            'Login' => (int)$user->id,
            'ID' => $id,
        ]);
    }

    /**
     * @param object $user
     * @param int $id
     *
     * @throws ApiException
     *
     * @return void
     */
    public function join(object $user, int $id): void
    {
        $this->request(self::JOIN_FUNDIST_URL, [
            'Login' => (int)$user->id,
            'ID' => $id,
        ]);
    }

    /**
     * @param object $user
     *
     * @throws ApiException
     *
     * @return void
     */
    public function cancel(object $user): void
    {
        $this->request(self::CANCEL_FUNDIST_URL, [
            'Login' => (int)$user->id,
        ]);
    }

    /**
     * @param object $user
     *
     * @throws ApiException
     *
     * @return void
     */
    public function finish(object $user): void
    {
        $this->request(self::FINISH_FUNDIST_URL, [
            'Login' => (int)$user->id,
        ]);
    }
}
