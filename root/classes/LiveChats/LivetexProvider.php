<?php
namespace eGamings\WLC\LiveChats;

use eGamings\WLC\RestApi\ApiException;

class LivetexProvider
    extends AbstractProvider {

    function __construct($liveChatType) {
        parent::__construct($liveChatType);
    }

    public function getDataApi(Array $params)
    {
        throw new ApiException(_('Method is not supported'), 404);
    }

    public function SendRequest(array $params)
    {
        $url = $this->LiveChatBaseApiUrl . '/v2/chats/list/' . '?&' . http_build_query($params);

        $ch = curl_init();
        $headers = array('Authorization: Basic ' .  base64_encode($this->LiveChatApiLogin . ':' . $this->LiveChatApiPassword));

        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
        );

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        return [
            'response'      => $response,
            'code'          => $code,
            'header_size'   => $header_size
        ];
    }

    public function refactResponseConversations(array $response)
    {
        $contacts = [];

        if (!empty($response)) {
            foreach ($response['results'] as $conversation) {
                $login = null;
                $email = null;

                foreach ($conversation['prechats_hidden'] as $prechat) {
                    if ($prechat['name'] == 'client_id') {
                        $login = $prechat['value'];
                    } elseif ($prechat['name'] == 'email') {
                        $email = $prechat['value'];
                    }
                }

                foreach ($conversation['prechats_chat'] as $prechat) {
                    if ($prechat['name'] == 'email') {
                        $email = $prechat['value'];
                        break;
                    }
                }

                if (!empty($login)) {
                    $contacts['Login'] = $login;
                }

                $employees = [];

                foreach ($conversation['employee'] as $employee) {
                    $lastname = !empty($employee['last_name']) ? ($employee['last_name'] . ' ') : '';
                    $employees[$employee['id']] = $lastname . $employee['first_name'];
                }

                $contacts['VisitorEmail'] = !empty($email) ? $email : '';
                $contacts['EmployeeName'] = (!empty($conversation['employee'][0]['last_name']) ? $conversation['employee'][0]['last_name'] : '') . (!empty($conversation['employee'][0]['first_name']) ? ' ' . $conversation['employee'][0]['first_name'] : '');
                $contacts['EmployeeEmail'] = !empty($conversation['employee'][0]['email']) ? $conversation['employee'][0]['email'] : '';
                $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s', $conversation['created_at'])->format('Y-m-d H:i:s');
                $contacts['Added'] = $dt;
                $contacts['Comment'] = '';

                foreach ($conversation['messages'] as $message) {
                    if (!empty($message['is_delivered'])) {
                        $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s', $message['created_at'])->format('Y-m-d H:i:s');
                    }

                    $type = '';

                    if ($message['employee'][0]['id'] == 1)
                        $type = 'visitor';
                    else
                        $type = 'employee_' . $employees[$message['employee'][0]['id']];

                    if ($contacts['Comment'] !== '')
                        $contacts['Comment'] .= "\n";

                    $contacts['Comment'] .= $dt . ' ' . $type . ':' . $message['text'];
                }
            }
        }

        return $contacts;
    }

    public function syncChatByCron()
    {
        if (!empty($this->LiveChatApiLogin)) {
            $response = explode(',', $this->GetLastContact());

            $params = [
                'fields' => 'id,site,created_at,prechats_chat,prechats_hidden,employee(id,first_name,last_name,email),messages(id,text,employee,visitor,is_delivered,created_at)',
            ];

            if (isset($response[0]) && $response[0] == '1' && !empty($response[1])) {
                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $response[1])->format('Y-m-d\TH:i:s');
                $params['q'] = 'site_ids=' . $this->LiveChatID . ' created_at>' . $dt;
            } else {
                $params['q'] = 'site_ids=' . $this->LiveChatID;
            }

            $apiResponse = $this->SendRequest($params);

            $response = $apiResponse['response']; // run the whole process
            $code = $apiResponse['code'];
            $header_size = $apiResponse['header_size'];

            $body = substr($response, $header_size);

            if ($code != 200) {
                echo 'Error occured <br/>HTTP CODE: ', $code, ';<br/> Request error';
                return false;
            }

            $body = json_decode($body, 1);

            if ($body === null) {
                echo 'Error occured <br/>HTTP CODE: ', $code, ';<br/> JSON decode error';
                return false;
            }

            $contact = $this->refactResponseConversations($body);

            if (!empty($contact)) {
                $this->AddContact($contact);
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

}