<?php
namespace eGamings\WLC\Chat;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\System;

class Chat
{
    private const USER_NICK_GET_FUNDIST_URL = '/User/Get';
    private const USER_NICK_ADD_FUNDIST_URL = '/User/Add';
    private const USER_NICK_UPDATE_FUNDIST_URL = '/User/Update';
    private const USER_INFO_GET_FUNDIST_URL = '/UserInfo/Get';

    private $system;

    public function __construct()
    {
        $this->system = System::getInstance();
    }

    public function getAllChatRooms($userID): string {
        $url = '/Chat/AllChatRooms';
        $transactionId = $this->system->getApiTID($url);
        $hash = md5('Chat/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userID . '/' . _cfg('fundistApiPass'));

        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );
        $url .= '?' . http_build_query($params);

        return $this->system->runFundistAPI($url);
    }

    public function authenticateUser($user)
    {
        if ($user) {
            $userID = (int)$user->id;
            $url = '/Chat/Login/?&Login=' . $userID;
            $transactionId = $this->system->getApiTID($url);
            $hash = md5('Chat/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userID . '/' . _cfg('fundistApiPass'));

            $params = Array(
                'Password' => $user->api_password,
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserID' => $userID,
                'Email' => $user->email,
                'Login' => $user->login,
                'UserIP' => System::getUserIP(),
                'AdditionalUserIP' => json_encode(System::getUserIP(true)),
                'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
            );
            $url .= '&' . http_build_query($params);
            return $this->system->runFundistAPI($url);
        }
    }

    /**
     * @param object $user
     *
     * @return array|string
     *
     * @throws ApiException
     */
    public function getUserNickname(object $user)
    {
        return $this->request(self::USER_NICK_GET_FUNDIST_URL, [
            'userId' => (int)$user->id,
        ]);
    }

    /**
     * @param object $user
     * @param string $nickname
     *
     * @return array|string
     *
     * @throws ApiException
     */
    public function createUserNickname(object $user, string $nickname)
    {
        return $this->request(self::USER_NICK_ADD_FUNDIST_URL, [
            'userId' => (int)$user->id,
            'nickname' => $nickname,
        ], true);
    }

    /**
     * @param object $user
     * @param string $nickname
     *
     * @return array|string
     *
     * @throws ApiException
     */
    public function updateUserNickname(object $user, string $nickname)
    {
        return $this->request(self::USER_NICK_UPDATE_FUNDIST_URL, [
            'userId' => (int)$user->id,
            'nickname' => $nickname,
        ], true);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param bool $post
     *
     * @return array|string
     *
     * @throws ApiException
     */
    private function request(string $uri, array $data = [], bool $post = false)
    {
        $url = '/Chat' . $uri . '?';
        $transactionId = $this->system->getApiTID($url);
        $hash = md5('Chat/User/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));

        $params = array_merge([
            'TID' => $transactionId,
            'Hash' => $hash,
        ], $data);

        $url .= '&' . http_build_query($params);

        $response = $this->system->runFundistAPI($url, 0, '', $post);

        [$code, $data] = explode(',', $response, 2);


        switch ((int)$code) {
            case 14:
                throw new ApiException(_('Invalid request parameters'), 405);
            case 54:
                throw new ApiException(_('Nickname already exist'), 422);
            case 55:
                throw new ApiException(_('Chat user not found'), 403);
            case 57:
                throw new ApiException(_('User nickname creation error'), 500);
            case 58:
                throw new ApiException(_('User nickname contains invalid symbols'), 403);
            case 59:
                throw new ApiException(_('Chat user already exist'), 403);
            case 60:
                throw new ApiException(_('Unknown user'), 403);
            case 61:
                throw new ApiException(_('Error create WLCChayUserInfo'), 500);
            case 62:
                throw new ApiException(_('Error update WLCChayUserInfo'), 500);
            case 63:
                throw new ApiException(_('User info not found'), 403);
            case 64:
                throw new ApiException(_('The user information is already there. Use update'), 403);
            
            default:
                break;
        }

        return json_decode($data, true) ?: $data;
    }

    public function getUserInfo($Room, $OccupantID, $Role)
    {
        return $this->request(self::USER_INFO_GET_FUNDIST_URL, [
            'Room' => $Room,
            'OccupantID' => $OccupantID,
            'Role' => $Role
        ], true);
    }

}
