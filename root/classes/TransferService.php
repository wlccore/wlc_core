<?php

declare(strict_types=1);

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Traits\HasFuncoreRequests;
use Exception;

final class TransferService
{
    use HasFuncoreRequests;

    private const TRANSFER_RUN_FUNDIST_URL = '/Transfers/Run';
    private const TRANSFER_GET_CONFIG_FUNDIST_URL = '/Transfers/GetConfig';

    /**
     * @param object $sender
     * @param array $data
     *
     * @return void
     * @throws ApiException
     */
    public function run(object $sender, array $data): void
    {
        $this->request(self::TRANSFER_RUN_FUNDIST_URL, array_merge([
            'Login' => $sender->id,
            'Password' => $sender->api_password,
        ], $data));
    }

    /**
     * @param object $user
     *
     * @return array
     * @throws ApiException
     */
    public function getConfig(object $user): ?array
    {
        return $this->request(self::TRANSFER_GET_CONFIG_FUNDIST_URL, [
            'Login' => $user->id,
            'Password' => $user->api_password,
        ])['result'] ?? null;
    }

    /**
     * @param object $user
     * @param string $code
     *
     * @return bool
     * @throws ApiException
     */
    public function sendConfirmationEmail(object $user, string $code): bool
    {
        try {
            $result = $this->request('/WLCAccount/SendMail/TransferConfirmation', [
                'Login' => $user->id,
                'Password' => $user->api_password,
                'Code' => $code,
            ], [$user->id]);
        } catch (Exception $ex) {
            if ($ex->getMessage() === 'Event with name TransferConfirmation doesn\'t exists.'
                || strpos($ex->getMessage(), 'Template for this event TransferConfirmation') === 0
            ) {
                return false;
            }

            throw $ex;
        }

        if (!empty($result['Error'])) {
            throw new Exception($result['Error']);
        }        

        return true;
    }
}
