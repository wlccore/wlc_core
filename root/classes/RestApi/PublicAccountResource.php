<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="public account",
 *     description="Join a public account and convert it to a permanent account"
 * )
 */
class PublicAccountResource extends AbstractResource
{
    private const ACTION_JOIN = 'join';
    private const ACTION_STATS = 'stats';

    private $Settings;

    /**
     * @throws ApiException
     */
    public function __construct()
    {
        $this->Settings = _cfg('publicAccount') ?? [];
        if (empty($this->Settings['enabled']) || $this->Settings['enabled'] !== true) {
            throw new ApiException(_('This feature is disabled'), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/publicAccount/{action}",
     *     description="Join a public account or convert it to a permanent",
     *     tags={"public account"},
     *     @SWG\Parameter(
     *         name="action",
     *         type="string",
     *         in="path",
     *         required=true,
     *         description="Action"
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Email"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string",
     *                 description="Password"
     *             ),
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string",
     *                 description="Currency"
     *             ),
     *              @SWG\Property(
     *                 property="useJwt",
     *                 type="boolean",
     *                 description="Jwt"
     *             ),
     *             @SWG\Property(
     *                 property="registrationPromoCode",
     *                 type="string",
     *                 description="Bonus promo code"
     *             )
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="If authorization is successful",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="loggedIn",
     *                     type="string",
     *                     example="1"
     *                 ),
     *                 @SWG\Property(
     *                     property="tradingURL",
     *                     type="string",
     *                     description="Url for https://www.tradingview.com/"
     *                 ),
     *                 @SWG\Property(
     *                     property="jwtToken",
     *                     type="string",
     *                     description="jwtToken returns in case system use jwt instead of cookies. For future requests [Authorization: Bearer <jwtToken>]"
     *                 ),
     *                 @SWG\Property(
     *                     property="affiliateId",
     *                     type="string",
     *                     example="12345"
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Result of convert the account to permanent",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
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
    public function post($request, $query, $params = [])
    {
        switch ($params['action']) {
            case 'join':
                if (User::isAuthenticated()) {
                    throw new ApiException(_('Not available for authorized user'), 400);
                }

                if (empty($this->Settings['currency'])) {
                    throw new ApiException(_('Invalid currency'), 400);
                }

                $system = System::getInstance();
                $url = '/WLCClassifier/PublicAccounts/';
                $transactionId = $system->getApiTID($url);
                $hash = md5('WLCClassifier/PublicAccounts/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
                $params = [
                    'Action' => self::ACTION_JOIN,
                    'Currency' => $this->Settings['currency'],
                    'TID' => $transactionId,
                    'Hash' => $hash,
                ];
                $url .= '?&' . http_build_query($params);

                $response = $system->runFundistAPI($url);
                $result = explode(',', $response, 2);

                if ($result[0] === '1') {
                    $data = json_decode($result[1], true);
                    $AuthResource = new AuthResource();
                    $loginData = [
                        'login' => $data['Email'],
                        'password' => $data['Password'],
                        'useJwt' => $request['useJwt'] ?? false,
                    ];
                    $loginResult = $AuthResource->put($loginData, []);

                    if ($loginResult['result']['loggedIn'] === '1') {

                        if (!User::isAuthenticated()) {
                            throw new ApiException(_('User is not authorized'), 401);
                        }
                        $user = (new User())->checkUser();

                        $amount = $this->Settings['amount'] ?? 5;
                        $url = '/Balance/Set/?&Login=' . (int)$user->id;
                        $transactionId = $system->getApiTID($url);
                        $hash = md5('Balance/Set/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/0/' . $amount . '/' . $user->id . '/' . $this->Settings['currency'] . '/' . _cfg('fundistApiPass'));

                        $params = [
                            'System' => '0',
                            'Amount' => $amount,
                            'Currency' => $this->Settings['currency'],
                            'TID' => $transactionId,
                            'Hash' => $hash,
                        ];

                        $url .= '&' . http_build_query($params);

                        $response = explode(',', $system->runFundistAPI($url));
                        if ($response[0] !== '1') {
                            throw new ApiException(_($response[1]), 400);
                        }
                    }

                    return $loginResult;
                }

                throw new ApiException(_($result[1]), 400);
            case 'makePermanent':
                if (empty($request['email']) || empty($request['password']) || empty($request['currency'])) {
                    throw new ApiException(_('email, password, currency required'), 400);
                }

                if (!User::isAuthenticated()) {
                    throw new ApiException(_('User is not authorized'), 401);
                }

                $User = User::getInstance();
                $login = $User->userData->id;
                $publicEmail = $User->userData->email;

                $url = '/WLCAccount/PublicAccounts/?&Login=' . $login;
                $transactionId = $User->getApiTID($url, $login);
                $hash = md5('WLCAccount/PublicAccounts/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
                $params = [
                    'TID' => $transactionId,
                    'Hash' => $hash,
                ];
                $url .= '&' . http_build_query($params);

                $response = explode(',', $User->runFundistAPI($url));

                if ($response[0] !== '1') {
                    throw new ApiException(_($response[1]), 400);
                }

                $regData = [
                    'fromPublicAccount' => $publicEmail,
                    'ageConfirmed' => true,
                    'agreedWithTermsAndConditions' => true,
                    'currency' => $request['currency'],
                    'email' => $request['email'],
                    'password' => $request['password'],
                    'passwordRepeat' => $request['password'],
                    'useJwt' => $request['useJwt'] ?? false,
                    'skipEmailVerification' => !empty($this->Settings['skipEmailVerification']) && $this->Settings['skipEmailVerification'] == true,
                    'registrationPromoCode' => $request['registrationPromoCode'],
                    'bonusIdPublicAccount' => (int)$this->Settings['bonusId'],
                    'affiliateId' => $request['affiliateId'] ?? ''
                ];
                $result = (new UserProfileResource())->post($regData, []);

                (new AuthResource())->delete([], []); //logout from public account
                return array_merge_recursive(['returnCode' => 201], $result);
            default:
                throw new ApiException(_('Wrong action'), 400);
        }
    }

    /**
     * @SWG\Get(
     *     path="/publicAccount",
     *     description="Returns public account statistics",
     *     tags={"public account"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         in="query",
     *         description="Currency"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="Total",
     *                  type="number",
     *                  example=50
     *              ),
     *              @SWG\Property(
     *                  property="Available",
     *                  type="number",
     *                  example=32
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */
    public function get($request, $query, $params = [])
    {
        $system = System::getInstance();
        $url = '/WLCClassifier/PublicAccounts/';
        $transactionId = $system->getApiTID($url);
        $hash = md5('WLCClassifier/PublicAccounts/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [
            'Action' => self::ACTION_STATS,
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        if (!empty($query['currency'])) {
            $params['Currency'] = $query['currency'];
        }
        $url .= '?&' . http_build_query($params);

        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);
        if ($response[0] !== '1') {
            throw new ApiException(_('Bad request'), 400);
        }

        return json_decode($result[1], true);
    }
}
