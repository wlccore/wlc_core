<?php
namespace eGamings\WLC\LiveChats;

use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Db;

abstract class AbstractProvider extends System
{
    protected $LiveChatType;
    protected $LiveChatID;
    protected $LiveChatBaseUrl;
    protected $LiveChatBaseApiUrl;
    protected $LiveChatApiKey;
    protected $LiveChatAppId;
    protected $LiveChatSrcScript;
    protected $LiveChatApiLogin;
    protected $LiveChatApiPassword;
    protected $LiveChatSyncCron;

    public function __construct($liveChatConfig)
    {
        $this->LiveChatType         = !empty($liveChatConfig['LiveChatType']) ? $liveChatConfig['LiveChatType'] : '';
        $this->LiveChatID           = !empty($liveChatConfig['LiveChatID']) ? $liveChatConfig['LiveChatID'] : '';
        $this->LiveChatBaseUrl      = !empty($liveChatConfig['LiveChatBaseUrl']) ? $liveChatConfig['LiveChatBaseUrl'] : '';
        $this->LiveChatBaseApiUrl   = !empty($liveChatConfig['LiveChatBaseApiUrl']) ? $liveChatConfig['LiveChatBaseApiUrl'] : '';
        $this->LiveChatApiKey       = !empty($liveChatConfig['LiveChatApiKey']) ? $liveChatConfig['LiveChatApiKey'] : '';
        $this->LiveChatAppId        = !empty($liveChatConfig['LiveChatAppId']) ? $liveChatConfig['LiveChatAppId'] : '';
        $this->LiveChatSrcScript    = !empty($liveChatConfig['LiveChatSrcScript']) ? $liveChatConfig['LiveChatSrcScript'] : '';
        $this->LiveChatApiLogin     = !empty($liveChatConfig['LiveChatApiLogin']) ? $liveChatConfig['LiveChatApiLogin'] : '';
        $this->LiveChatApiPassword  = !empty($liveChatConfig['LiveChatApiPassword']) ? $liveChatConfig['LiveChatApiPassword'] : '';
        $this->LiveChatSyncCron     = !empty($liveChatConfig['LiveChatSyncCron']) ? $liveChatConfig['LiveChatSyncCron'] : false;
    }

    /**
     * Data sync chat by cron
     *
     * @public
     * @method syncChatByCron
     * @return {boolean}
     */
    abstract public function syncChatByCron();

    /**
     * Get data of API chat
     *
     * @public
     * @method getDataApi
     * @param $dataType {string}
     * @return mixed
     */
    abstract public function getDataApi(Array $params);

    /**
     * Send request API
     *
     * @public
     * @method
     * @param $params {array}
     * @return mixed
     */
    abstract public function SendRequest(Array $params);

    /**
     * Сonvert response to view of database write
     *
     * @public
     * @method refactResponseConversations
     * @param {array} $response
     * @return {array}
     */
    public function refactResponseConversations(Array $response)
    {
        $contact = [
            'VisitorName'   => !empty($response['visitor']['name']) ? $response['visitor']['name'] : '',
            'VisitorEmail'  => !empty($response['visitor']['email']) ? $response['visitor']['email'] : '',
            'EmployeeName'  => !empty($response['agents'][0]['name']) ? $response['agents'][0]['name'] : '',
            'EmployeeEmail' => !empty($response['agents'][0]['email']) ? $response['agents'][0]['email'] : '',
            'Comment'       => ''
        ];

        if (!empty($contact['VisitorEmail'])) {
            $row = Db::fetchRow(
                'SELECT `id`, `email` ' .
                'FROM `users` ' .
                'WHERE `email` = "' . Db::escape($contact['VisitorEmail']) . '" ' .
                'LIMIT 1'
            );

            if ($row !== false)
                $contact['Login'] = (int)$row->id;
        }

        foreach ($response['chat']['messages'] as $message) {
            $dt = !empty($message['timestamp']) ? date('Y-m-d H:i:s', $message['timestamp']) : '';

            if ($contact['Comment'] !== '')
                $contact['Comment'] .= "\n";

            $contact['Comment'] .= $dt . ' ' . $message['type'] . ':' . $message['message'];
        }

        return $contact;
    }

    /**
     * Get data of chat
     *
     * @public
     * @method GetChatData
     * @return {array}
     */
    public function GetChatData()
    {
        $chat_data =  [
            'LiveChatType' => $this->liveChatType,
            'LiveChatID'   => $this->liveChatID
        ];

        if (isset($_SESSION['user']) and !empty($_SESSION['user'])) {
            $profileData = (array)User::getProfileData($_SESSION['user']['id']);
            $chat_data['user'] = [
                'user_id' => $_SESSION['user']['id'],
                'name'    => $profileData['first_name'] . ' ' . $profileData['last_name'],
                'email'   => $profileData['email'],
                'phone'   => $profileData['phone1'] . ' ' . $profileData['phone2']
            ];
        }

        return $chat_data;
    }

    /**
     * Add user livechat conversation in Fundist
     *
     * @public
     * @method AddContact
     * @param $data {array}
     * @return Fundist API response
     */
    public function AddContact(Array $data)
    {
        $url = '/User/AddContact/?';
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/AddContact/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $data['EmployeeEmail'] . '/' . $data['EmployeeName'] . '/'  . _cfg('fundistApiPass'));
        $data['TID'] = $transactionId;
        $data['Hash'] = $hash;

        $url .= '&' . http_build_query($data);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    /**
     * Get last added contact
     *
     * @public
     * @method GetLastContact
     * @return Fundist API response
     */
    public function GetLastContact()
    {
        $url = '/User/GetLastContact/?';
        $data = [];
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/GetLastContact/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/'  . _cfg('fundistApiPass'));
        $data['TID'] = $transactionId;
        $data['Hash'] = $hash;

        $url .= '&' . http_build_query($data);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    /**
     * Default setters and getters here
     *
     * @public
     * @method __call
     * @param $method
     * @param $params
     * @return {mixed}
     * @throws {Error}
     */
    public function __call($method, $params) {
        $type = substr($method,0,3);
        $key = substr($method,3);

        if($type=='get') return $this->$key;
        else if($type=='set') $this->$key = $params[0];
        else throw new ApiException(get_class($this) . '::' . $method . ' ' . _('does not exists'), 400);
    }

}