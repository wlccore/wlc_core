<?php
namespace eGamings\WLC\LiveChats;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Cache;

class LadeskProvider
    extends AbstractProvider {

    function __construct($liveChatType) {
        parent::__construct($liveChatType);
    }

    public function getDataApi(Array $params)
    {
        $dataType = $params['dataType'];
        $recordId = !empty($params['recordId']) ? $params['recordId'] : '';

        switch ($dataType) {
            case 'startLoad':
                return $this->getStartData();
                break;
            case 'conversationMessages':
                return $this->getConversationMessages($recordId);
                break;
            case 'agentInfo':
                return $this->getAgentInfo($recordId);
                break;
            case 'customerInfo':
                return $this->getCustomerInfo($recordId);
                break;
            default:
                throw new ApiException(_('Data type is not found'), 404);
                break;
        }
    }

    /**
     * Get agent info
     *
     * @param $agentID
     * @return mixed
     * @throws ApiException
     */
    public function getAgentInfo($agentID)
    {
        if (empty($agentID))
            throw new ApiException(_('Invalid request parameters'), 404);

        $requestParams = [
            'type' => 'agents',
            'action' => $agentID
        ];

        $response = json_decode($this->SendRequest($requestParams), true);

        return $response['response'];
    }

    /**
     * Get customer info
     *
     * @param $cuctomerID
     * @return mixed
     * @throws ApiException
     */
    public function getCustomerInfo($cuctomerID)
    {
        if (empty($cuctomerID))
            throw new ApiException(_('Invalid request parameters'), 404);

        $requestParams = [
            'type' => 'customers',
            'action' => $cuctomerID
        ];

        $response = json_decode($this->SendRequest($requestParams), true);

        return $response['response'];
    }

    /**
     * Get messages of conversation
     *
     * @param $conversationID
     * @return array
     * @throws ApiException
     */
    public function getConversationMessages($conversationID)
    {
        if (empty($conversationID))
            throw new ApiException(_('Invalid request parameters'), 404);

        $requestParams = [
            'type' => 'conversations',
            'action' => $conversationID . '/messages'
        ];

        $response = json_decode($this->SendRequest($requestParams), true);

        if (!empty($response['response']['groups'])) {
            $msGroup = array_values(array_filter($response['response']['groups'], function($group) {
                return $group['rtype'] === 'C';
            }));
        }

        return $this->refactMessages(!empty($msGroup[0]) ? $msGroup[0] : []);
    }

    /**
     * Get data of start load
     *
     * @return array
     */
    public function getStartData()
    {
        $startLoadData = [
            'LiveChatAppId' => $this->LiveChatAppId,
            'LiveChatSrcScript' => $this->LiveChatSrcScript
        ];

        $startLoadData['departments'] = Cache::result('chat:departments', function() {
            $result = [];
            $curl_response = $this->SendRequest(['type' => 'departments']);

            if ($curl_response !== false) {
                $result = json_decode($curl_response, true);
            }

            return !empty($result['response']['departments']) ? $result['response']['departments']   : [];
        });

        $startLoadData['buttons'] = Cache::result('chat:buttons', function() {
            $result = [];
            $buttons = [];

            $curl_response = $this->SendRequest(['type' => 'widgets']);

            if ($curl_response !== false) {
                $result = json_decode($curl_response, true);
            }

            if (!empty($result['response']['widgets'])) {
                foreach ($result['response']['widgets'] as $widget) {
                    if ($widget['rtype'] === 'C')
                        $buttons[explode('-', $widget['language'], 2)[0]][$widget['departmentid']] = $widget['contactwidgetid'];
                }
            }

            return $buttons;
        });

        return $startLoadData;
    }

    public function SendRequest(Array $params)
    {
        if (!empty($params) && !empty($params['type'])) {
            $action= !empty($params['action']) ? '/' . $params['action'] : '';
            $url = $this->LiveChatBaseApiUrl . '/api/' . $params['type'] . $action . '?apikey=' . $this->LiveChatApiKey;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $curl_response = curl_exec($ch);
            curl_close($ch);

            return $curl_response;
        }
    }

    /**
     * Bringing message to required format
     *
     * @public
     * @method refactMessages
     * @param {array} $msGroup
     * @return {array}
     */
    private function refactMessages(Array $msGroup)
    {
        $msgResult = [];

        if (!empty($msGroup['messages'])) {
            $visitor = $this->getCustomerInfo($msGroup['userid']);

            $visitor = [
                'name' => (!empty($visitor['firstname']) ? $visitor['firstname'] : '') . (!empty($visitor['lastname']) ? ' ' . $visitor['lastname'] : ''),
                'email' => !empty($visitor['email']) ? $visitor['email'] : ''
            ];

            $agent = [];
            foreach ($msGroup['messages'] as $message) {
                $msgUserId = $message['userid'];

                $msg = [
                    'timestamp' => strtotime($message['datecreated']),
                    'message' => $message['message']
                ];

                if ($msgUserId !== $msGroup['userid'] && empty($agent)) {
                    $agent = $this->getAgentInfo($message['userid']);

                    $agent = [
                        'name' => (!empty($agent['firstname']) ? $agent['firstname'] : '') . (!empty($agent['lastname']) ? ' ' . $agent['lastname'] : ''),
                        'email' => !empty($agent['email']) ? $agent['email'] : ''
                    ];
                }

                $msg['type'] = ($msgUserId !== $msGroup['userid'])
                    ? ($agent['name'] . ' (' . $agent['email'] . ')')
                    : $visitor['name'] . ' (' . $visitor['email'] . ')';

                $msgResult['chat']['messages'][] = $msg;
            }

            $msgResult['visitor'] = $visitor;
            $msgResult['agents'][] = $agent;
        }

        return $msgResult;
    }

    //Method is not supported
    public function syncChatByCron()
    {
        return false;
    }

}