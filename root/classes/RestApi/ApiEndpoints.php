<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Auth2FAGoogle;
use eGamings\WLC\Cache;
use eGamings\WLC\Config;
use eGamings\WLC\Email;
use eGamings\WLC\KycAml;
use eGamings\WLC\Logger;
use eGamings\WLC\NonceService;
use eGamings\WLC\Router;
use eGamings\WLC\RateLimiter;
use eGamings\WLC\Service\Captcha;
use eGamings\WLC\Service\CookieProtection;
use eGamings\WLC\Service\CountryNonResidence;
use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\Recaptcha;

/**
 * @SWG\Info(
 *   title="WLC Core Api (PHP)",
 *   version=WLCCORE_VERSION
 * )
 *
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="wlc-site-address.com",
 *   basePath="/api/v1",
 *   produces={"application/json"},
 * )
 */

/**
 * @SWG\Parameter(
 *     name="lang",
 *     description="Current language",
 *     type="string",
 *     in="query"
 * )
 *
 * @SWG\Parameter(
 *     name="currency",
 *     description="Prefered currency",
 *     type="string",
 *     in="query"
 * )
 */

/**
 * @class ApiEndpoints
 * @namespace eGamings\WLC\RestApi
 * @uses eGamings\WLC\Logger
 * @uses eGamings\WLC\Router
 */
class ApiEndpoints
{
    static function getApiRoutes()
    {
        static $route_mapping = [
            'api/v1/bootstrap' => BootstrapResource::class,
            'api/v1/currencies' => CurrencyResource::class,
            'api/v1/countries' => CountryResource::class,
            'api/v1/states' => StatesResource::class,
            'api/v1/wins' => WinResource::class,
            'api/v1/jackpots' => JackpotResource::class,
            'api/v1/games' => GamesResource::class,
            'api/v1/games/launch' => GamesResource::class,
            'api/v1/games/sorting[/{type}]' => GamesSortingResource::class,
            'api/v1/games/sorts[/{sorttype}[/{type}]]' => GamesSortsResource::class,
            'api/v1/paymentSystems' => PaymentSystemResource::class,
            'api/v1/withdrawals[/{type}]' => WithdrawalResource::class,
            'api/v1/deposits' => DepositResource::class,
            'api/v1/transactions' => TransactionResource::class,
            'api/v1/auth' => AuthResource::class,
            'api/v1/auth/2fa/google' => Auth2FAGoogleResource::class,
            'api/v1/auth/social' => SocialAuthResource::class,
            'api/v1/auth/socialLink' => SocialLinkResource::class,
            'api/v1/auth/social/oauth_cb/{provider}' => 'socialOAuthCallbackEndpoint',
            'api/v1/auth/refreshToken' => RefreshTokenResourse::class,
            'api/v1/auth/check' => AuthCheckResourse::class,
            'api/v1/authBy/google2fa' => AuthBy2FAGoogleResource::class,
            'api/v1/profiles[/{action}[/{post_action}]]' => UserProfileResource::class,
            'api/v1/userInfo' => UserInfoResource::class,
            'api/v1/userPassword[/{action}]' => UserPasswordResource::class,
            'api/v1/userSelfExclusion[/{action}]' => UserSelfExclusionResource::class,
            'api/v1/bonuses[/{id}]' => BonusResource::class,
            'api/v1/achievements' => AchievementResource::class,
            'api/v1/tournaments[/{id:\d+}[/{action}]]' => TournamentsResource::class,
            'api/v1/tournaments/{type:\D+}' => TournamentsResource::class,
            'api/v1/store[/{id:\d+}]' => StoreResource::class,
            'api/v1/store/{type:\D+}' => StoreResource::class,
            'api/v1/loyalty[/{action}]' => LoyaltyResource::class,
            'api/v1/supportEmail' => SupportEmailResource::class,
            'api/v1/binaryOptions' => BinaryOptionsResource::class,
            'api/v1/binaryOptionsFeed' => BinaryOptionsFeedResource::class,
            'api/v1/binaryOptionsDeposits' => BinaryOptionsDepositsResource::class,
            'api/v1/binaryOptionsWithdrawals' => BinaryOptionsWithdrawalsResource::class,
            'api/v1/stats/topWins' => StatsTopWinsResource::class,
            'api/v1/stats[/{action}/{type}]' => PaymentStatsResource::class,
            'api/v1/storage[/{key}]' => StorageResource::class,
            'api/v1/liveChat' => LiveChatResource::class,
            'api/v1/sms' => SmsProviderResource::class,
            'api/v1/sms/callback[/{provider}]' => SmsCallbackResource::class,
            'api/v1/messages[/{id:\d+[_]?\d+[_]?\d+}[/{action}]]' => MessagesResource::class,
            'api/v1/favorites[/{id:\d+}]' => FavoriteGamesResource::class,
            'api/v1/thirdParty[/{service}]' => ThirdPartySystemsResource::class,
            'api/v1/bets' => BetsResource::class,
            'api/v1/validate[/{type}]' => ValidateResource::class,
            'api/v1/docs[/{id:\d+}[/{file}]]' => DocumentsResource::class,
            'api/v1/docs/{type:\D+}' => DocumentsResource::class,
            'api/v1/affTrack[/{image}]' => AffiliateTrackResource::class,
            'api/v1/affVisitor' => AffiliateVisitorResource::class,
            'api/v1/balance' => BalanceResource::class,
            'api/v1/referrals' => ReferralsResource::class,
            'api/v1/sportsbook[/{action}]' => SportsBookResource::class,
            'api/v1/sessionCheck' => SessionCheckResource::class,
            'api/v1/tempUsers[/{action}]' => TempUsersResource::class,
            'api/v1/realityCheck' => RealityCheckResource::class,
            'api/v1/paycryptos[/{action}/{type}[/{currencies}]]' => PaycryptosResource::class,
            'api/v1/fasttrack[/{action}[/{userid}]]' => FastTrackResource::class,
            'api/v1/providers/hotEvents' => ProvidersHotEventsResource::class,
            'api/v1/providers/tablesInfo' => ProvidersTablesInfoResource::class,
            'api/v1/cashback[/{id}]' => CashbackResource::class,
            'api/v1/zendesk' => ZendeskResource::class,
            'api/v1/trustDevices' => TrustDevicesResource::class,
            'api/v1/publicAccount[/{action}]' => PublicAccountResource::class,
            'api/v1/kycaml' => KycAmlResource::class,
            'api/v1/transfer[/{action}]' => TransferResource::class,
            'api/v1/acceptTermsOfService' => UserAcceptTermsOfServiceResource::class,
            'api/v1/withdrawalRequests' => WithdrawalRequestsResource::class,
            'api/v1/chat/password' => ChatPasswordResource::class,
            'api/v1/chat/user' => ChatResource::class,
            'api/v1/chat/rooms' => ChatResource::class,
            'api/v1/chat/user/data' => ChatNicknameResource::class,
            'api/v1/chat/userinfo[/{action}]' => ChatUserInfoResource::class,
            'api/v1/lastSuccessfulDeposit' => LastSuccessfulDepositResource::class,
            'api/v1/commissions/romanian' => RomanianCommissionsResource::class,
            'api/v1/lastSuccessfulWithdrawal' => LastSuccessfulWithdrawalResource::class,
            'api/v1/static/categories[/{slug}]' => WordpressCategoriesResource::class,
            'api/v1/static/posts[/{slug}]' => WordpressPostsResource::class,
            'api/v1/static/pages[/{slug}]' => WordpressPagesResource::class,
            'api/v1/static/seo/{dataType}' => WordpressSeoResource::class,
            'api/v1/wptopdf' => WpToPdfResource::class,
            'api/v1/deposits/prestep' => DepositPrestepResource::class,
            'api/v1/countrynonresidence' => CountryNonResidenceResource::class,
            'api/v1/socialNetworks' => SocialNetworksResource::class,
            'api/{version}/banners' => BannersResource::class,
            'api/v1/seo' => SeoResource::class,
            'api/v1/metrics' => MetricsResource::class,
            'api/v1/sumDeposits' => SumDepositResource::class,
            'api/v1/publicSocketsData' => PublicSocketsDataResource::class,
            'api/v1/streamWheel' => StreamWheelResource::class,
            'api/v1/streamWheel/participants' => StreamWheelParticipantsResource::class,
            'api/v1/wallets' => WalletsResource::class,
            'api/v1/kycform' => KYCResource::class,
            'api/v1/LastNGameActions' => LastNGameActionsResource::class,
            'api/v1/youtube' => YoutubeResource::class,
            'api/v1/estimate' => EstimateResource::class,
            'api/v1/reports' => ReportsResource::class,
        ];

        return $route_mapping;
    }

    /**
     * Call of $method from resources by wlc to get metrics
     *
     * @public
     * @static
     * @method metricsResource
     * @param {string} $method Method of class MetricsResource.
     * List of methods:
     * * get($request, $query, $params = []) - Check params of request
     * * post($request, $query, $params = []) - Initiate password change process
     * * put($request, $query, $params = []) - Confirm password change
     * * patch($request, $query, $params = []) - Update current user password logged
     * @param {array} $request Request parameters
     * @param {array} $query Query parameters
     * @param {array} [$params=array()] Route parameters:
     * * $params['action'] - type of action
     * @return {mixed}
     * @throws {ApiException}
     */
    static function metricsEndpoint($method, $request, $query, $params = [])
    {
        $resource = new MetricsResource();

        return $resource->handle($method, $request, $query, $params);
    }

    /**
     * @param $method
     * @param $request
     * @param $query
     * @param $params
     * @return array
     * @throws ApiException
     */
    static function socialOAuthCallbackEndpoint($method, $request, $query, $params)
    {
        $resource = new OAuthResource($params['provider']);

        return $resource->handle($method, $request, $query, $params);
    }

    /**
     * Mapping API url to resource endpoint
     */
    static function apiEndpoint()
    {

        $route = Router::getRoute();

        $routeMappingBase = self::getApiRoutes();
        $routeMappingHook = System::hook('api:routes');
        $routeMapping = array_merge($routeMappingBase, (is_array($routeMappingHook)) ? $routeMappingHook : []);

        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routeMapping) {
            foreach($routeMapping as $routePath => $routeHandler) {
                $r->addRoute(['OPTIONS','GET','PUT','POST','PATCH','DELETE','HEAD'], $routePath, $routeHandler);
            }
        });

        $httpMethod = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $method = strtolower($httpMethod);
        $query = $_GET;
        $response = null;
        $siteConfig = Config::getSiteConfig();

        $ip = System::getUserIP();

        try {
            $sessionId = session_id();

            if ($sessionId && !self::checkNonce($sessionId, $route)) {
                (new User())->logout();
                throw new ApiException('Wrong nonce', 403);
            }

            if (_cfg('enableCookieProtection') && $sessionId) {
                $cookieProtection = new CookieProtection();
                if (!$cookieProtection->check($sessionId, $route)) {

                    if (!empty($_SESSION['user']['email'])
                        && !Cache::get(CookieProtection::KEY_PREFIX_EMAIL. $sessionId)
                        && $cookieProtection->get($sessionId)
                    ) {
                        $msg = sprintf(_('cookie_protection_message'),
                            $_SERVER['HTTP_USER_AGENT'],
                            gmdate("H:i:s d.m.Y"),
                            $_SERVER['REMOTE_ADDR'],
                        );
                        $subj = _('Login attempt has been prevented');
                        if (Email::send($_SESSION['user']['email'], $subj, $msg)) {
                            Cache::set(CookieProtection::KEY_PREFIX_EMAIL. $sessionId, 1);
                        }
                    }

                    (new User())->logout();
                    throw new ApiException(
                        _('Something went wrong during login process. Please check the correctness of the entered data and try again.'),
                        403
                    );
                }
            }

            if ($route == "api/v1/trustDevices") {
                if (RateLimiter::getInstance()->isBlocked($method . $route, $ip)) {
                    $message = _('Exceeded the number of attempts to enter the verification code. You can try again in %s minutes');
                    $lockTime = _cfg('lockTime2FA') ? _cfg('lockTime2FA') : 5;
                    $message = sprintf($message, $lockTime);
                    throw new ApiExceptionWithBlockUntil($message, 403, null, [], $lockTime);
                }
            }

            if ($route == "api/v1/authBy/google2fa" && !_cfg('disableRateLimiterGoogle2FA')) {
                if (RateLimiter::getInstance()->isBlocked($method . $route . Auth2FAGoogle::POSTFIX_AUTHKEY, $ip)) {
                    $message = _('Exceeded the number of attempts to enter the auth key. You can try again in %s minutes');
                    $lockTime = _cfg('lockTimeGoogle2FAAuthKey') ? _cfg('lockTimeGoogle2FAAuthKey') : 60;
                    $message = sprintf($message, $lockTime);
                    throw new ApiExceptionWithBlockUntil($message, 403, null, [], $lockTime);
                }

                if (RateLimiter::getInstance()->isBlocked($method . $route . Auth2FAGoogle::POSTFIX_CODE2FA, $ip)) {
                    $message = _('Exceeded the number of attempts to enter the verification code. You can try again in %s minutes');
                    $lockTime = _cfg('lockTimeGoogle2FACode') ? _cfg('lockTimeGoogle2FACode') : 5;
                    $message = sprintf($message, $lockTime);
                    throw new ApiExceptionWithBlockUntil($message, 403, null, [], $lockTime);
                }
            }

            if ($route == "api/v1/sms") {
                $lockTime = _cfg('smsLimitAttempts') ? _cfg('smsLimitAttempts') : 5;
                RateLimiter::getInstance()->limit($method . $route, $ip, $lockTime, $lockTime * 60);
                if (RateLimiter::getInstance()->isBlocked($method . $route, $ip)) {
                    switch ($method) {
                        case 'post':
                            $message = _('Exceeded the number of attempts to receive the sms code. You can try again in %s minutes');
                            break;
                        case 'put':
                            $message = _('Exceeded the number of attempts to enter the verification code. You can try again in %s minutes');
                            break;
                        default:
                            $message = _('Too many requests');
                    }

                    $message = sprintf($message, $lockTime);
                    throw new ApiExceptionWithBlockUntil($message, 403, null, [], $lockTime);
                }
            }


            if ($route == "api/v1/userPassword" && !_cfg('disableRateLimiterUserPassword')) {
                $limit = ( (_cfg('rateLimitUserPasswordLimit') && _cfg('rateLimitUserPasswordLimit') > 0) ? _cfg('rateLimitUserPasswordLimit') : 5 ) + 1;
                $period = (_cfg('rateLimitUserPasswordPeriod') && _cfg('rateLimitUserPasswordPeriod') > 0) ? _cfg('rateLimitUserPasswordPeriod') : 60;
                $block = (_cfg('rateLimitUserPasswordBlock') && _cfg('rateLimitUserPasswordBlock') > 0) ? _cfg('rateLimitUserPasswordBlock') : 60;

                RateLimiter::getInstance()->limit($method . $route, $ip, $limit, $period, $block);
                if (RateLimiter::getInstance()->isBlocked($method . $route, $ip)) {
                    switch ($method) {
                        case 'patch':
                            $message = _('The number of attempts to change the password has been exceeded. You can try again in %s minutes');
                            break;
                        default:
                            $message = _('Too many requests');
                    }

                    $message = sprintf($message, $block / 60);
                    throw new ApiException($message, 429);
                }
            }

            if (
                ($method == "put" && $route == "api/v1/profiles/promocode" && RateLimiter::getInstance()->isBlocked($method . $route, $ip)) ||
                ($method == "get" && $route == "api/v1/bonuses" && isset($query['PromoCode']) && RateLimiter::getInstance()->isBlocked($method . $route . 'PromoCode', $ip))
            ) {
                $minutes = (_cfg('rateLimitPromocodesBlock') && _cfg('rateLimitPromocodesBlock') > 0)
                    ? _cfg('rateLimitPromocodesBlock') / 60
                    : 60;
                throw new ApiException(sprintf(_('Too many requests to Promocodes'), $minutes), 429);
            }

            if ($route === "api/v1/supportEmail" && RateLimiter::getInstance()->isBlocked($method . $route, $ip)) {
                throw new ApiException(_('Too many requests to api/v1/supportEmail'), 429);
            }

            if (!_cfg('disableRateLimiter')) {
                if ($method != "options" && RateLimiter::getInstance()->isBlocked($method . $route, $ip)) {
                    throw new ApiException(_('Too many requests'), 429);
                }

                if ( _cfg('rateLimitProfiles')) {
                    $limit = (_cfg('rateLimitProfilesLimit') && _cfg('rateLimitProfilesLimit') > 0) ? _cfg('rateLimitProfilesLimit') : 10;
                    $period = (_cfg('rateLimitProfilesPeriod') && _cfg('rateLimitProfilesPeriod') > 0) ? _cfg('rateLimitProfilesPeriod') : 5*60;
                    $block = (_cfg('rateLimitProfilesBlock') && _cfg('rateLimitProfilesBlock') > 0) ? _cfg('rateLimitProfilesBlock') : 5*60;
                    if (
                        ($method == "post" && $route == "api/v1/profiles")
                        || ($method == "put" && $route == "api/v1/profiles/email")
                        || ($method == "put" && $route == "api/v1/profiles/login")
                        || ($method == "put" && $route == "api/v1/transfer")
                    ) {
                        RateLimiter::getInstance()->limit($method . $route, $ip, $limit, $period, $block);
                    }
                }

                if (!empty($siteConfig['UserDepositsLimiter']['IsUserDepositsRequestLimitEnabled'])) {
                    $limit = ($siteConfig['UserDepositsLimiter']['UserDepositsRequestLimitCount'] ?? 0) ?: 5;
                    $period = ($siteConfig['UserDepositsLimiter']['UserDepositsRequestLimitPeriod'] ?? 0) ?: 60;
                    $block = ($siteConfig['UserDepositsLimiter']['UserDepositsRequestLimitBlockTime'] ?? 0) ?: 3600;

                    if ($method == "post" && $route == "api/v1/deposits") {
                        RateLimiter::getInstance()->limit($method . $route, $ip, $limit, $period, $block);
                    }
                }

                if (_cfg('rateLimitPromocodes')) {
                    $limit = (_cfg('rateLimitPromocodesLimit') && _cfg('rateLimitPromocodesLimit') > 0) ? _cfg('rateLimitPromocodesLimit') : 5;
                    $period = (_cfg('rateLimitPromocodesPeriod') && _cfg('rateLimitPromocodesPeriod') > 0) ? _cfg('rateLimitPromocodesPeriod') : 60*30;
                    $block = (_cfg('rateLimitPromocodesBlock') && _cfg('rateLimitPromocodesBlock') > 0) ? _cfg('rateLimitPromocodesBlock') : 60*60;
                    if (
                        ($method == "put" && $route == "api/v1/profiles/promocode")
                        || ($method == "get" && $route == "api/v1/bonuses" && isset($query['PromoCode']))
                    ) {
                        RateLimiter::getInstance()->limit($method . $route . (isset($query['PromoCode']) ? 'PromoCode' : ''), $ip, $limit, $period, $block);
                    }
                }

                if (_cfg('rateLimitSupportEmail')) {
                    $limit = _cfg('rateLimitSupportEmailLimit') > 0 ? _cfg('rateLimitSupportEmailLimit') : 3;
                    $period = _cfg('rateLimitSupportEmailPeriod') > 0 ? _cfg('rateLimitSupportEmailPeriod') : 1*60;
                    $block = _cfg('rateLimitSupportEmailBlock') > 0 ? _cfg('rateLimitSupportEmailBlock') : 5*60;

                    if ($route === "api/v1/supportEmail") {
                        RateLimiter::getInstance()->limit($method . $route, $ip, $limit, $period, $block);
                    }
                }
            }

            $request = $_REQUEST;
            if ($method == 'post' || $method == 'put' || $method == 'patch' || $method === 'delete') {
                $requestInput = file_get_contents('php://input');
                $request = json_decode($requestInput, true);
                if ($request === null && !empty($GLOBALS['_' . strtoupper($method)])) {
                    $request = $GLOBALS['_' . strtoupper($method)];
                }
            }

            $uniqueUserMark = !empty($request['login'])
                ? $request['login']
                : ((!empty($request['phoneCode']) && !empty($request['phoneNumber']))
                    ? ($request['phoneCode'] . $request['phoneNumber'])
                    : ''
                );

            self::blockingByNotAcceptedTermsOfService($route);

            if((new CountryNonResidence(User::getInstance()))->isBlocked($route, $method, $request)) {
                throw new ApiException(_('Your country is on the forbidden list'), 428);
            }

            if ($route == "api/v1/auth" && _cfg('enableCaptcha') && !empty($uniqueUserMark)) {
                $captchaResponse = $_SERVER['HTTP_X_CAPTCHA'] ?? '';
                $captchaService = new Captcha($uniqueUserMark);

                if ($captchaResponse && $captchaService->existsRecord()) {
                    if ($captchaService->proceedResponse($captchaResponse) === false) {
                        self::throwCaptchaException(sprintf("data:image/jpeg;base64,%s", base64_encode(
                            $captchaService
                                ->buildCaptcha()
                                ->getCaptcha()
                                ->render()
                        )), _('Code is not valid'));
                    }
                } else {
                    if ($captchaService->isBanned()) {
                        switch (Captcha::$BANNED_BY) {
                            case Captcha::$HOUR:
                                self::throwCaptchaException(sprintf("data:image/jpeg;base64,%s", base64_encode(
                                    $captchaService
                                        ->buildCaptcha()
                                        ->getCaptcha()
                                        ->render()
                                )));
                                break;
                            case Captcha::$DAY:
                                $captchaService->showDayBan();
                        }
                    }
                }
            }

            $rand = rand();
            $recaptchaKey = "recaptcha" . $method . $route;
            if (_cfg('recaptchaLog')) {
                error_log("XXX " . $rand . " recaptchaKey = " . $recaptchaKey);
            }
            if (self::isCaptchaProtected($method, $route)) {
                if (_cfg('recaptchaLog')) {
                    error_log("XXX " . $rand . " isCaptchaProtected");
                }
                $recaptcha = new Recaptcha();
                if ($recaptcha->enabled() && !in_array($ip, $recaptcha->whiteList())) {
                    if (_cfg('recaptchaLog')) {
                        error_log("XXX " . $rand . " enabled");
                    }
                    if (RateLimiter::getInstance()->isBlocked($recaptchaKey, "0.0.0.0")) {
                        if (_cfg('recaptchaLog')) {
                            error_log("XXX " . $rand . " isBlocked");
                        }
                        $recaptchaToken = $_SERVER['HTTP_X_RECAPTCHA'] ?? "";
                        if (_cfg('recaptchaLog')) {
                            error_log("XXX " . $rand . " recaptchaToken = " . $recaptchaToken);
                        }
                        if (!$recaptcha->check($recaptchaToken)) {
                            if (_cfg('recaptchaLog')) {
                                error_log("XXX " . $rand . " check fail");
                            }
                            throw new ApiException(_('Too many requests. Captcha required'), 429);
                        }
                        if (_cfg('recaptchaLog')) {
                            error_log("XXX " . $rand . " check ok");
                        }
                    }

                    $limit = (_cfg('recaptchaLimit') && _cfg('recaptchaLimit') > 0) ? _cfg('recaptchaLimit') : 20;
                    $period = (_cfg('recaptchaPeriod') && _cfg('recaptchaPeriod') > 0) ? _cfg('recaptchaPeriod') : 10;
                    $block = (_cfg('recaptchaBlock') && _cfg('recaptchaBlock') > 0) ? _cfg('recaptchaBlock') : 30*60;

                    RateLimiter::getInstance()->limit($recaptchaKey, "0.0.0.0", $limit, $period, $block);
                }
            }

            $endpointProcessor = '';
            $endpointParams = [];

            try {
                $routeInfo = $dispatcher->dispatch($httpMethod, $route);
            } catch(\Exception $ex) {
                $routeInfo = array(\FastRoute\Dispatcher::NOT_FOUND);
            }

            switch ($routeInfo[0]) {
                case \FastRoute\Dispatcher::FOUND:
                    $endpointProcessor = $routeInfo[1];
                    $endpointParams = $routeInfo[2];
                    break;

                case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    // ... 405 Method Not Allowed
                    throw new ApiException(_('Method Not Allowed'), 404);
                    break;

                case \FastRoute\Dispatcher::NOT_FOUND:
                default:
                    // ... 404 Not Found
                    throw new ApiException(_('Unknown route path'), 404);
                    break;
            }

            if (method_exists(__CLASS__, $endpointProcessor)) {
                $body = self::$endpointProcessor($method, $request, $query, $endpointParams);
            } else if (function_exists($endpointProcessor)) {
                $body = $endpointProcessor($method, $request, $query, $endpointParams);
            } else if (is_callable($endpointProcessor)) {
                $body = call_user_func_array($endpointProcessor, [$method, $request, $query, $endpointParams]);
            } else if (is_callable([new $endpointProcessor, 'handle'])) {
                $body = call_user_func_array([new $endpointProcessor, 'handle'], [$method, $request, $query, $endpointParams]);
            } else {
                throw new ApiException(_('Api endpoint not found'), 404);
            }

            $code = 200;
            if (is_array($body) && !empty($body['returnCode'])) {
                $code = $body['returnCode'];
                unset($body['returnCode']);
            }
            $response = self::buildResponse($code, 'success', $body);
        } catch(ApiExceptionWithBlockUntil $e) {
            $errorCode = $e->getCode() ? $e->getCode() : 400;
            $blockUntil = (new \DateTime())->modify('+'. $e->getBlockUntil() .' minutes')->format('Y-m-d H:i:s');
            $response = self::buildResponse($errorCode, 'error', null, $e->getErrors(), $blockUntil);
        } catch(ApiExceptionWithData $e) {
            $errorCode = $e->getCode() ? $e->getCode() : 400;
            $response = self::buildResponse($errorCode, 'success', ['authKey' => $e->getData()]);
        } catch (ApiException $e) {
            $errorCode = $e->getCode() ? $e->getCode() : 400;

            // Authentication failure limit
            if ($errorCode === 403 && !_cfg('disableRateLimiter') && !self::routesWithDisabledRateLimiter($route)) {
                if ($route == "api/v1/trustDevices") {
                    $limitVal = _cfg('numberOfAttempts2FA') ? _cfg('numberOfAttempts2FA') : 5;
                    RateLimiter::getInstance()->limit($method . $route, $ip, $limitVal);
                } else {
                    $limitVal = (_cfg('rateLimitAuth') && _cfg('rateLimitAuth') > 3) ? _cfg('rateLimitAuth') : 3;
                    RateLimiter::getInstance()->limit($method . $route, $ip, $limitVal);
                }
            }

            $response = self::buildResponse($errorCode, 'error', null, $e->getErrors());
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if ($message) {
                Logger::log($message);
            }

            try {
                $body = json_decode($message, true);
            } catch (\Exception $e) {
                $body = null;
            }

            $response = self::buildResponse($e->getCode() ? $e->getCode() : 400, 'error', null, $body);
        }
        http_response_code($response['code']);

        if ($route == 'api/v1/metrics') {
            header('Content-Type: text/plain; charset=utf-8');
            $result = $response['data'];
        } else {
            header('Content-Type: application/json; charset=utf-8');
            $result = json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        header('Content-Length: ' . strlen($result));

        if ($response['code'] == 200) {
            $sent_headers = [];
            foreach(headers_list() as $header) {
                $headerArr = explode(':', $header, 2);
                $sent_headers[strtolower($headerArr[0])] = $headerArr[1];
            }

            if (empty($sent_headers['cache-control']) && empty($sent_headers['expires'])) {
                header('Cache-Control: no-cache, no-store');
            }

            if (!User::isAuthenticated()) {
                header('X-Accel-Expires: 30');
            }
        }

        if (RateLimiter::getInstance()->isBlocked($recaptchaKey, "0.0.0.0")) {
            header('X-Recaptcha: ' . $recaptcha->getSiteKey());
        }

        exit($result);
    }

    public static function throwCaptchaException(string $captchaData, ?string $errorMessage = null): void
    {
        $errors = [
            'captcha' => $captchaData
        ];

        if ($errorMessage !== null) {
            $errors['error'] = $errorMessage;
        }

        throw new ApiException('', 403, null, $errors);
    }

    public static function buildResponse(int $code, string $status, $data = null, $errors = null, $aditonal = null): array
    {
        $result = [
          'code' => $code,
          'status' => $status
        ];

        if ($aditonal) {
            $result['blockUntil'] = $aditonal;
        }

        if ($status === 'error') {
            $result['errors'] = $errors;
        } else {
            $result['data'] = $data;
        }

        return $result;
    }

    /**
     * @param string $key
     * @param string $route
     *
     * @return bool
     */
    private static function checkNonce(string $key, string $route): bool
    {
        $excludedRoutes = [
            'api/v1/bootstrap',
            'api/v1/games',
            'api/v1/metrics',
        ];

        if (in_array($route, $excludedRoutes, true)) {
            return true;
        }

        // temporary
        if (stripos($route, 'api/v1/docs') !== false) {
            return true;
        }

        $token = '';
        if (!empty($_SESSION['user'])) {
            $nonceService = new NonceService();
            $token = $nonceService->get($key);
        }

        return !$token || $token === ($_SERVER['HTTP_X_NONCE'] ?? null);
    }

    private static function isCaptchaProtected(string $method, string $route): bool
    {
        $routes = [
            'put api/v1/auth',
            'post api/v1/profiles',
            'put api/v1/profiles/email',
            'put api/v1/profiles/login',
            'post api/v1/userpassword',
        ];
        return in_array(strtolower($method . ' ' . $route), $routes);
    }

    /**
     * @param $route
     * @return void
     * @throws ApiException
     */
    private static function blockingByNotAcceptedTermsOfService($route): void
    {
        $routesWhiteListForNotAcceptedTermsOfService = [
            'api/v1/auth',
            'api/v1/auth/check',
            'api/v1/auth/refreshToken',
            'api/v1/TermsOfService',
            'api/v1/userInfo',
            'api/v1/profiles',
            'api/v1/withdrawals',
            'api/v1/withdrawals/queries',
            'api/v1/withdrawals/status',
            'api/v1/withdrawals/complete',
            'api/v1/docs',
            'api/v1/docs/types',
            'api/v1/docs/extensions',
            'api/v1/supportEmail',
            'api/v1/games',
            'api/v1/games/launch',
            'api/v1/bootstrap',
            'api/v1/paymentSystems',
            'api/v1/acceptTermsOfService',
            'api/v1/wptopdf',
            'api/v1/publicSocketsData',
            'api/v1/countrynonresidence',
        ];

        if (
            !empty(_cfg('termsOfService')) &&
            !empty($_SESSION['user']) &&
            !User::isCurrentUserAcceptCurrentTermsOfService() &&
            !in_array($route, $routesWhiteListForNotAcceptedTermsOfService)
        ) {
            throw new ApiException('You need to accept terms of service');
        }
    }

    private static function routesWithDisabledRateLimiter(string $route): bool
    {
        $routesWithDisabledRateLimiter = [
            'api/v1/chat/userinfo',
        ];

        if(in_array($route, $routesWithDisabledRateLimiter)) {
            return true;
        }

        return false;
    }
}

