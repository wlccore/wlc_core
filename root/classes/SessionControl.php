<?php

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;

/**
 * @codeCoverageIgnore
 */
class SessionControl
{
    /**
     * @return string
     */
    private function getDeviceFingerPrint(): string
    {
        return $_SERVER['HTTP_X_UA_FINGERPRINT'] ?? '';
    }

    /**
     * @param $loggedUser
     * @return void
     * @throws ApiException
     */
    public function checkOpenSessions($loggedUser): void
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        if (!$this->getDeviceFingerPrint()) {
            error_log("FingerPrint not enabled for " . _cfg('site'));
            return;
        }

        $userID = $loggedUser->userData->id;
        if (!$userID) {
            throw new ApiException(_('User not found'), 401);
        }

        $currentSessionID = session_id();
        $sessions = $this->getSessions($userID);

        if (empty($sessions)) {
            Db::query(sprintf('INSERT INTO `session_history` (`user_id`,`finger_print`,`session_id`,`add_date`,`updated`) VALUES (%u,"%s","%s",NOW(),NOW())',
                    $userID, $this->getDeviceFingerPrint(), $currentSessionID)
            );
        } elseif ($sessions[0]->finger_print === $this->getDeviceFingerPrint()) {
            Db::query(sprintf('UPDATE `session_history` SET `updated` = NOW(), `session_id` = "%s" WHERE user_id = %u and finger_print = "%s"',
                    $currentSessionID, $userID, $this->getDeviceFingerPrint())
            );
        } elseif ($sessions[0]->finger_print !== $this->getDeviceFingerPrint()) {
            session_write_close();
            foreach ($sessions as $row) {
                session_id($row->session_id);
                @session_start();
                session_destroy();
                session_write_close();
            }
            session_id($currentSessionID);
            @session_start();
            session_write_close();

            Db::query(sprintf('UPDATE `session_history` SET `finger_print` = "%s",`session_id` = "%s", `updated` = NOW() WHERE `user_id` = %u',
                    $this->getDeviceFingerPrint(), $currentSessionID, $userID)
            );
        }
    }

    /**
     * @param $userID
     * @return array
     */
    private function getSessions($userID): array
    {
        $result = Db::fetchRows(
            sprintf('SELECT * FROM `session_history` WHERE `user_id` = %u ORDER BY `updated` DESC',
                Db::escape($userID)
            )
        );

        if ($result === false) {
            $result = [];
        }

        return (array)$result;
    }

}
