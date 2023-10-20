<?php

namespace eGamings\WLC;


/**
 * Class Messages
 * @package eGamings\WLC
 */
class Messages extends System
{
    /**
     * Get current user messages
     *
     * @param $login - User ID
     * @return mixed
     * @throws \Exception
     */
    public function getMessages($login)
    {
        $url = '/User/GetMessages/?';
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/GetMessages/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Login' => $login,
            'UserIP' => $_SERVER['REMOTE_ADDR'],
        ];

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $result = $data[1];
        } else {
            throw new \Exception('Error: ' . $response, 400);
        }

        return $result;
    }

    /**
     * Update current user message status
     *
     * @param $login - User ID
     * @param $listID - UsersList ID
     * @param $status - Status of message (1 - message readed, -100 - message deleted)
     * @return mixed
     * @throws \Exception
     */
    public function updateMessageStatus($login, $listID, $status)
    {
        $url = '/User/UpdateMessageStatus/?';
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/UpdateMessageStatus/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Login' => $login,
            'IDList' => $listID,
            'Status' => $status,
            'UserIP' => $_SERVER['REMOTE_ADDR'],
        ];

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $result = $data[1];
        } else {
            throw new \Exception('Error: ' . $response, 400);
        }

        return $result;
    }

    /**
     * Action is open link from message
     *
     * @public
     * @method openLinkMessage
     * @param {array} $dataRequest
     * @return {mixed}
     */
    public function updateMessageOpenLink($dataRequest)
    {
        $login = (int)$dataRequest['id_user'];
        $url = '/User/OpenMailFromLink/?';

        $user = new User();
        $userData = $user->getUserById($login);

        if (empty($userData)) {
            throw new \Exception(_('User not found'), 401);
        }

        $system = new System();
        $transactionId = $system->getApiTID($url, $login);

        $hash = md5('User/OpenMailFromLink/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Login' => $login,
            'IDList' => (int)$dataRequest['id_list'],
            'IDListExport' => (int)$dataRequest['id_list_export'],
            'UserIP' => self::getUserIP(),
        ];

        $url .= '&' . http_build_query($params);
        $response = $system->runFundistAPI($url);

        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $result = $data[1];
        } else {
            throw new \Exception('Error: ' . $response, 400);
        }

        return $result;
    }

    /**
     * Check required params of request
     *
     * @public
     * @method isCorrectMailParams
     * @param {string|array} $msgParams
     * @return {boolean}
     */
    public function isCorrectMailParams($msgParams)
    {
        if (!empty($msgParams)) {
            if (is_array($msgParams)) {
                return (!empty($msgParams['id_list'])
                    && !empty($msgParams['id_list_export'])
                    && !empty($msgParams['id_user'])
                );
            } else {
                $msgParams = explode('_', $msgParams);
                return count($msgParams) === 3;
            }
        }

        return false;
    }

    /**
     * Check is mailing link
     *
     * @public
     * @method isMailingLink
     * @param {array} [$fields=[]]
     * @return {boolean}
     */
    public function isMailingLink($fields = [])
    {
        return !empty($fields['link_from_mailing']);
    }

}