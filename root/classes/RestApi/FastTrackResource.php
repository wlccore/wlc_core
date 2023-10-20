<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\System;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="fasttrack",
 *     description="Fast track operator API"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="userblocks",
 *     type="object",
 *     @SWG\Property(
 *         property="active",
 *         type="boolean"
 *     ),
 *     @SWG\Property(
 *         property="type",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="note",
 *         type="string"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="userconsents",
 *     type="object",
 *     @SWG\Property(
 *         property="opted_in",
 *         type="boolean"
 *     ),
 *     @SWG\Property(
 *         property="type",
 *         type="string"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *    definition="userdetails",
 *    type="object",
 *    @SWG\Property(
 *        property="address",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="birth_date",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="city",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="country",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="currency",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="email",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="first_name",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="is_blocked",
 *        type="boolean"
 *    ),
 *    @SWG\Property(
 *        property="is_excluded",
 *        type="boolean"
 *    ),
 *    @SWG\Property(
 *        property="language",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="last_name",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="mobile",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="mobile_prefix",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="origin",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="postal_code",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="roles",
 *        type="array",
 *        @SWG\Items(
 *            type="string"
 *        )
 *    ),
 *    @SWG\Property(
 *        property="sex",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="title",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="user_id",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="username",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="verified_at",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="registration_code",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="registration_date",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="affiliate_reference",
 *        type="string"
 *    ),
 *    @SWG\Property(
 *        property="market",
 *        type="string"
 *    )
 * )
 */

/**
 * Class FastTrackResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\System
 * @uses eGamings\WLC\User
 */

class FastTrackResource extends AbstractResource
{
    private const AVAILABLE_ACTIONS_GET = ['userdetails', 'userblocks', 'userconsents'];
    private const AVAILABLE_ACTIONS_POST = ['reconciliation', 'userconsents', 'authenticate'];
    private const FIELDS_MAPPING = [
        'address' => 'Address',
        'birth_date' => 'DateOfBirth',
        'city' => 'City',
        'country' => 'Country',
        'currency' => 'Currency',
        'email' => 'Email',
        'first_name' => 'Name',
        'is_blocked' => 'Status',
        'is_excluded' => '',
        'language' => 'Lang',
        'last_name' => 'LastName',
        'mobile' => 'Phone',
        'origin' => 'Domain',
        'postal_code' => 'PostalCode',
        'roles' => 'Category',
        'sex' => 'Gender',
        'title' => '',
        'user_id' => 'Login',
        'username' => 'Nick',
        'verified_at' => 'EmailVerifiedDatetime',
        'registration_code' => '',
        'registration_date' => 'AddDate',
        'affiliate_reference' => 'AffiliateID',
        'market' => '',
        'segmentation' => 'segmentation',
    ];

    /**
     * @throws ApiException
     */
    public function __construct()
    {
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || $_SERVER['HTTP_AUTHORIZATION'] !== $this->getAuthentication()) {
            throw new ApiException('Unauthorized', 401);
        }
    }

    /**
     * @SWG\Get(
     *     path="/fasttrack/userdetails/{userid}",
     *     description="Fetch user data at different moments",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="userid",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="User ID"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User data",
     *         @SWG\Schema(
     *             ref="#/definitions/userdetails"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/fasttrack/userblocks/{userid}",
     *     description="Fetch  user blocks data",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="userid",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="User ID"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User blocks data",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="blocks",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/userblocks"
     *                )
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/fasttrack/userconsents/{userid}",
     *     description="Fetch  user consent data at relevant times in the user lifecycle.",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="userid",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="User ID"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User consent data",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="consents",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/userconsents"
     *                )
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @param $request
     * @param $query
     * @param array $params
     * @throws ApiException
     */
    public function get($request, $query, $params = [])
    {
        $action = !empty($params['action']) ? $params['action'] : (!empty($query['action']) ? $query['action'] : null);
        $userid = !empty($params['userid']) ? $params['userid'] : (!empty($query['userid']) ? $query['userid'] : null);

        if (!in_array($action, self::AVAILABLE_ACTIONS_GET)) {
            throw new ApiException('Wrong FastTrack action', 400);
        }
        if (empty($userid)) {
            throw new ApiException('UserId not provided', 400);
        }

        $User = User::getInstance();
        $url = '/User/GetUserData/?&Login=' . $userid;
        $transactionId = $User->getApiTID($url, $userid);
        $hash = md5('User/GetUserData/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userid . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'GetAdditionalData' => true,
            'WithTags' => true,
            'WithLoyaltyUserInfo' => true,
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        $response = explode(',', $response, 2);
        if ($response[0] !== '1') {
            throw new ApiException(_('Request invalid. Error: ') . $response[1], 400);
        }

        $res = json_decode($response[1], JSON_OBJECT_AS_ARRAY);
        if (json_last_error() === JSON_ERROR_NONE) {
            header('HTTP/1.1 200 OK');
            header('Content-Type: application/json; encoding=utf-8');
            echo json_encode($this->reply($action, $res), JSON_UNESCAPED_UNICODE);
            die();
        }

        throw new ApiException(_('Request invalid. Error: ') . implode(',', $response), 400);
    }

    /**
     * @SWG\Post(
     *     path="/fasttrack/userconsents/{userid}",
     *     description="Update user consents at relevant times in the user lifecycle",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="userid",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="User ID"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="consents",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/userconsents"
     *                )
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/fasttrack/reconciliation",
     *     description="Reconciliation endpoint.",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="field",
     *                 type="string",
     *                 enum={"blocked", "active", "consent_email", "consent_sms"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User ids that has the key = true",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="users",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="string"
     *                 )
     *             ),
     *             @SWG\Property(
     *                 property="timestamp",
     *                 type="string"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/fasttrack/authenticate",
     *     description="Authentication endpoint.",
     *     tags={"fasttrack"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="sid",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User id",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="user_id",
     *                 type="number"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @param $request
     * @param $query
     * @param array $params
     * @throws ApiException
     */
    public function post($request, $query, $params = [])
    {
        $action = !empty($params['action']) ? $params['action'] : (!empty($query['action']) ? $query['action'] : null);
        $userid = !empty($params['userid']) ? $params['userid'] : (!empty($query['userid']) ? $query['userid'] : null);

        if (!in_array($action, self::AVAILABLE_ACTIONS_POST)) {
            throw new ApiException('Wrong FastTrack action', 400);
        }
        if (empty($userid) && !in_array($action, ['reconciliation', 'authenticate'])) {
            throw new ApiException('UserId not provided', 400);
        }

        switch ($action) {
            case 'userconsents':
                if (empty($request['consents'])) {
                    throw new ApiException('Consents not provided', 400);
                }
                $update = [];
                foreach ($request['consents'] as $concest) {
                    if ($concest['type'] == 'email') {
                        $update['EmailAgree'] = (int)$concest['opted_in'];
                    } elseif ($concest['type'] == 'sms') {
                        $update['SmsAgree'] = (int)$concest['opted_in'];
                    }
                }

                if ($update) {
                    $User = User::getInstance();
                    $url = '/User/Сonsents/?&Login=' . $userid;
                    $transactionId = $User->getApiTID($url, $userid);
                    $hash = md5('User/Сonsents/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userid . '/' . _cfg('fundistApiPass'));
                    $params = [
                        'TID' => $transactionId,
                        'Hash' => $hash,
                        'Fields' => $update,
                    ];
                    $url .= '&' . http_build_query($params);
                    $response = $User->runFundistAPI($url);
                    $response = explode(',', $response, 2)[0];
                    if ($response == 1) {
                        header('HTTP/1.1 200 OK');
                    } else {
                        header('HTTP/1.1 500 Internal Server Error');
                    }
                    die();
                }

                break;
            case 'reconciliation':
                $type = $request['field'] ?? null;
                header('HTTP/1.1 200 OK');
                header('Content-Type: application/json; encoding=utf-8');
                switch ($type) {
                    case 'blocked':
                    case 'active':
                    case 'consent_email':
                    case 'consent_sms':

                        $User = User::getInstance();
                        $url = '/User/Reconciliation/?&Login=fasttrack';
                        $transactionId = $User->getApiTID($url);
                        $hash = md5('User/Reconciliation/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/fasttrack/' . _cfg('fundistApiPass'));
                        $params = [
                            'TID' => $transactionId,
                            'Hash' => $hash,
                            'Type' => $type,
                        ];
                        $url .= '&' . http_build_query($params);
                        $response = $User->runFundistAPI($url);
                        $response = explode(',', $response, 2);

                        if ($response[0] == 1) {
                            echo $response[1];
                        } else {
                            echo json_encode(['error' => $response[1]], JSON_UNESCAPED_UNICODE);
                        }
                        break;
                    default:
                        echo json_encode(['users' => [], 'timestamp' => $this->formatDateTime()], JSON_UNESCAPED_UNICODE);
                }
                die();
            case 'authenticate':
                if (!_cfg('enableFastTrackAuthentication')) {
                    throw new ApiException('Authentication disabled', 400);
                }
                if (empty($request['sid'])) {
                    throw new ApiException('sid not provided', 400);
                }
                $User = User::getInstance();
                $storage = Front::Storage();
                $sid = $storage->getStorageData($User->userData->id, 'sid')['sid'] ?? null;

                if (!empty($sid) && $sid == $request['sid']) {
                    header('HTTP/1.1 200 OK');
                    header('Content-Type: application/json; encoding=utf-8');
                    echo json_encode(['user_id' => $User->userData->id]);
                    die();
                }

                throw new ApiException('Invalid sid', 400);
        }
    }

    /**
     * @param string $action
     * @param array $data
     * @return array
     */
    private function reply(string $action, array $data): array
    {
        $resp = [];
        switch ($action) {
            case 'userdetails':
                foreach (self::FIELDS_MAPPING as $k => $v) {
                    if (in_array($k, ['is_blocked', 'is_excluded'])) {
                        $resp[$k] = $data[$v] < 0 ? true : false;
                    } elseif (in_array($k, ['verified_at', 'registration_date'])) {
                        $resp[$k] = !empty($data[$v]) ? $this->formatDateTime($data[$v]) : '';
                    } elseif ($k == 'sex') {
                        $resp[$k] = !empty($data[$v]) ? ucfirst($data[$v]) : '';
                    } elseif ($k == 'user_id') {
                        $resp[$k] = !empty($data[$v]) ? explode('_', $data[$v])[1] : '';
                    } elseif ($k == 'mobile') {
                        if (!empty($data[$v])) {
                            $data['Phone'] = explode('-', $data['Phone']);
                            list($resp['mobile_prefix'], $resp['mobile']) = $data['Phone'];
                        } else {
                            $resp['mobile_prefix'] = $resp['mobile'] = '';
                        }
                    } elseif ($k == 'roles') {
                        $resp[$k] = [$data[$v] ?? ''];
                    } elseif ($k == 'segmentation') {
                        if (isset($data['LoyaltyUserInfo']['Level'])) {
                            $resp[$k]['loyalty_level'] = $data['LoyaltyUserInfo']['Level'];
                        }
                        $resp[$k]['tags'] = $data['Tags'];
                    } else {
                        $resp[$k] = !empty($data[$v]) ? $data[$v] : '';
                    }
                }

                break;
            case 'userblocks':
                $resp = [
                    'blocks' => [
                        [
                            'active' => $data['Status'] == 1 ? false : true,
                            'type' => 'Blocked',
                            'note' => '',
                        ],
                    ],
                ];

                break;

            case 'userconsents':
                $resp = [
                    'consents' => [
                        [
                            'opted_in' => (bool)$data['EmailAgree'],
                            'type' => 'email',
                        ],
                        [
                            'opted_in' => (bool)$data['SmsAgree'],
                            'type' => 'sms',
                        ],
                    ],
                ];

                break;
        }

        return $resp;
    }

    /**
     * @param string|null $date
     * @return string
     * @throws \Exception
     */
    private function formatDateTime(?string $date = null): string
    {
        $date = new \DateTime($date);
        return $date->setTimeZone(new \DateTimeZone('UTC'))->format("Y-m-d\TH:i:s.u\Z");
    }

    /**
     * @return string
     */
    private function getAuthentication() :string
    {
        $conf = _cfg('FastTrackConfig');
        $login = $conf['login'] ?: '';
        $pass = $conf['password'] ?: '';
        return 'Basic ' . base64_encode($login . ':' . $pass);
    }
}
