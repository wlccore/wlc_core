<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Auth2FAGoogle;
use eGamings\WLC\SocialAuth;
use eGamings\WLC\Storage\CookieStorage;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="oauth",
 *     description="OAuth"
 * )
 */

/**
 * @class OAuthResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\SocialAuth
 * @uses eGamings\WLC\User
 */
class OAuthResource extends AbstractResource
{
    /**
     * Provider name
     *
     * @property $providerName
     * @type string
     * @priotected
     */
    protected $providerName;

    /**
     * Constructor of class
     *
     * @public
     * @constructor
     * @method __construct
     * @param {string} $providerName
     */
    public function __construct($providerName)
    {
        $this->providerName = $providerName;
    }

    /**
     * @SWG\Get(
     *     path="/auth/social/oauth_cb/{provider}",
     *     description="Callback for the oauth provider",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="provider",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="OAuth provider",
     *         default="fb"
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         type="string",
     *         required=true,
     *         in="query",
     *         description="Authentication code"
     *     ),
     *     @SWG\Response(
     *         response="302",
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
     * Authentification of users by OAuth
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=array()]
     * @return {boolean|null}
     * @throws {\Exception}
     */
    public function get($request, $query, $params = [])
    {
        $social = new SocialAuth();
        $u = new User();
        $regUser = null;
        $provider = null;
        $result = null;

        try {
            $provider = $social->getProvider($this->providerName);
        } catch (\Exception $ex) {
        }

        if (!$provider) {
            return false;
        }
        $providerConfig = $social->getProviderConfig($this->providerName);

        $error = '';

        try {
            $accessToken = $social->verifyOAuthCodeFromProvider($this->providerName, $request, $query);
            $userInfo = $social->getUserInfo($this->providerName, $accessToken);
            if (!empty($providerConfig['onlyRegistration'])) {
                try {
                    $regUser = $u->getUserByEmail($userInfo['email']);
                    if (!$regUser) {
                        if (empty($userInfo['currency'])) $userInfo['currency'] = 'EUR';
                        if (empty($userInfo['password'])) $userInfo['password'] = User::passwordLib()->getRandomToken(12);
                        CookieStorage::getInstance()->set('social_user_info', $userInfo);

                        $result = $u->completeSocialRegistration($userInfo);
                    } else {
                        // Mark social record
                        $social->setUserSocialInfo($regUser, $userInfo);
                    }
                } catch (\Exception $ex) {
                    $error = $ex->getMessage();
                };
            }

            if (is_null($result)) {
                $result = $social->doSocialLoginForUser($userInfo);

                //Добавить 2FA Google
            }

        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }

        $socialRegisterPath = (_cfg('userSocialRegister')) ? _cfg('userSocialRegister') : '';


        $socialLocation = _cfg('site') . '/' . _cfg('language');

        if ($result) {
            $redirectParams = '';
            CookieStorage::getInstance()->set('social_user_info', $userInfo);

            /*
             * $_SESSION['just_login'] variable means that user was logged in with social network
             * User::loginWithSocialAccount
             */
            $justLogin = CookieStorage::getInstance()->get('just_login');
            if (!$justLogin) {

                if (!empty($providerConfig['redirect_params']) && is_array($providerConfig['redirect_params'])) {
                    $params_arr = [];
                    foreach ($providerConfig['redirect_params'] as $param_k => $param_v) {
                        $params_arr[] = $param_k . '=' . $param_v;
                    }
                    $redirectParams = implode('&', $params_arr);
                }

                // Add registration path
                $socialLocation .= $socialRegisterPath . (($redirectParams != '') ? '?' . $redirectParams : '');
                $socialLocation = str_replace('#social#', $this->providerName, $socialLocation);
            } else {
                CookieStorage::getInstance()->remove('just_login');
            }
        } else {
            if ($error) {
                $socialLocation .= '?error=SOCIAL_LOGIN_FAILED';
            }
        }

        if (_cfg('useJwtSocialAuth') && !empty($_SESSION['user']['id'])) {
            $AuthResource = new AuthResource();
            $data = [
                'jwtToken' => $AuthResource->setAccessJwtToken((int)$_SESSION['user']['id']),
                'refreshToken' => $AuthResource->setRefreshJwtToken((int)$_SESSION['user']['id']),
            ];

            $socialLocation .= (stripos($socialLocation, '?') === false ? '?' : '&') . http_build_query($data);
            header('Location: ' . $socialLocation);

            return $result;
        }

        //header('Location: ' . _cfg('site') . '/' . _cfg('language') . (($redirectParams != '') ? '?' . $redirectParams : ''));
        //header('Location: ' . _cfg('site') . '/' . _cfg('language')) . ($error != '' ? '?error=socialLoginFailed' : '');
        header('Location: ' . $socialLocation);

        return $result;
    }

    /**
     * Delete of users
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     */
    public function delete($request, $query, $params = [])
    {

    }
}
