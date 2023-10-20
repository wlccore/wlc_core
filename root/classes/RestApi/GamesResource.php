<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Front;
use eGamings\WLC\System;
use eGamings\WLC\Games;
use eGamings\WLC\User;
use eGamings\WLC\Utils;

/**
 * @SWG\Tag(
 *     name="games",
 *     description="Games"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="GameList",
 *     description="List of games",
 *     type="object",
 *     @SWG\Property(
 *         property="categories",
 *         type="array",
 *         description="Games categories",
 *         example={{"ID": "47", "Trans": {"en": "Blackjacks"}, "Tags": {"blackjacks"}, "Name": {"en": "Blackjacks"}, "menuId": "blackjacks"}},
 *         @SWG\Items(
 *             type="object"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="countriesRestrictions",
 *         type="object",
 *         description="Countries restrictions",
 *         example={"5": {"Countries": {"blr", "bwa"}, "ID": "11", "IDMerchant": "997", "IsDefault": "0", "Name": "blr,bwa"}}
 *     ),
 *     @SWG\Property(
 *         property="games",
 *         type="array",
 *         description="Games",
 *         @SWG\Items(
 *             @SWG\Property(
 *                 property="AR",
 *                 type="string",
 *                 enum={"16:9", "4:3"},
 *                 description="Aspect ratio"
 *             ),
 *             @SWG\Property(
 *                 property="Branded",
 *                 type="integer",
 *                 enum={0, 1},
 *                 description="Branded game"
 *             ),
 *             @SWG\Property(
 *                 property="CategoryID",
 *                 type="array",
 *                 description="Game categories",
 *                 example={"16", "25"},
 *                 @SWG\Items(
 *                     type="object"
 *                 )
 *             ),
 *             @SWG\Property(
 *                 property="Description",
 *                 type="object",
 *                 description="Game description",
 *                 example={"en": "Text"}
 *             ),
 *             @SWG\Property(
 *                 property="Freeround",
 *                 type="string",
 *                 description="The ability to use freespins in the game",
 *                 enum={"1", "0"}
 *             ),
 *             @SWG\Property(
 *                 property="ID",
 *                 type="string",
 *                 description="Game id",
 *                 example="175310"
 *             ),
 *             @SWG\Property(
 *                 property="Image",
 *                 type="string",
 *                 description="Game image",
 *                 example="/path/to/game/image.jpg"
 *             ),
 *             @SWG\Property(
 *                 property="LaunchCode",
 *                 type="string",
 *                 description="Game launch code",
 *                 example="game_launch_mb"
 *             ),
 *             @SWG\Property(
 *                 property="MerchantID",
 *                 type="string",
 *                 description="Game merchant id",
 *                 example="977"
 *             ),
 *             @SWG\Property(
 *                 property="MobileUrl",
 *                 type="string",
 *                 description="Game mobile url",
 *                 example="977/game_launch_mb"
 *             ),
 *             @SWG\Property(
 *                 property="Name",
 *                 type="object",
 *                 description="Game name",
 *                 example={"en": "Game name"}
 *             ),
 *             @SWG\Property(
 *                 property="Url",
 *                 type="object",
 *                 description="Game url",
 *                 example="977/game_launch"
 *             ),
 *             @SWG\Property(
 *                 property="hasDemo",
 *                 type="integer",
 *                 enum={0, 1},
 *                 description="Game demo"
 *             )
 *         )
 *     ),
 *     @SWG\Property(
 *         property="merchants",
 *         type="object",
 *         description="Games merchants",
 *         example={"977": {"ID": "977", "Name": "BoomingGame", "menuId": "boominggames"}}
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Game",
 *     description="Game launch params",
 *     type="object",
 *     @SWG\Property(
 *         property="config",
 *         type="object",
 *         description="Games config",
 *         @SWG\Property(
 *             property="AR",
 *             type="string",
 *             enum={"16:9", "4:3"},
 *             description="Aspect ratio"
 *         ),
 *         @SWG\Property(
 *             property="AuthToken",
 *             type="string",
 *             description="Auth token",
 *             example="anonymous"
 *         ),
 *     ),
 *     @SWG\Property(
 *         property="gameHtml",
 *         type="string",
 *         description="Game launch html",
 *         example="<iframe id='egamings_container' src='..'>...</iframe>"
 *     ),
 *     @SWG\Property(
 *         property="gameScript",
 *         type="string",
 *         description="Game launch script"
 *     ),
 *     @SWG\Property(
 *         property="merchant",
 *         type="string",
 *         description="Game merchant",
 *         example="mg"
 *     ),
 *     @SWG\Property(
 *         property="merchantId",
 *         type="string",
 *         description="Game merchant id",
 *         example="997"
 *     ),
 *     @SWG\Property(
 *         property="mobilePlatform",
 *         type="boolean",
 *         description="Support for mobile platforms"
 *     ),
 *     @SWG\Property(
 *         property="isRestricted",
 *         type="boolean",
 *         description="Is this game is restricted by user country geoip"
 *     )
 * )
 */

/**
 * @class GamesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\System
 */
class GamesResource extends AbstractResource
{
    /**
     * Get filter parameters for query
     *
     * @param array $query
     * @return array filter array
     */
    private static function getGamesFilter($query) {
        $merchant = empty($query['merchant']) ? 0 : $query['merchant'];
        $category = empty($query['category']) ? 0 : $query['category'];
        $order_by = empty($query['order_by']) ? 0 : $query['order_by'];

        $user_country = System::getGeoData();

        $filter = [
                'merchant' => $merchant,
                'category' => $category,
                'order_by' => $order_by,
                'user_country' => $user_country,
                'is_mobile' => Utils::isMobile()
        ];

        return $filter;
    }
    /**
     * Remove elements by tag name
     *
     * @protected
     * @method removeElementsByTagName
     * @param {string} $tagName
     * @param {\DOMDocument} $document
     */
    protected static function removeElementsByTagName($tagName, $document)
    {
        $nodeList = $document->getElementsByTagName($tagName);

        for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0;) {
            $node = $nodeList->item($nodeIdx);
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Get games catalog with filter
     *
     * @public
     * @method getGamesCatalog
     * @param {array} [$filter=array()]
     * @return {array|mixed}
     */
    public static function getGamesCatalog($filter = [])
    {
        $filterFields = [
            'merchant' => 'merchant',
            'category' => 'category',
            'order_by' => 'orderBy',
            'user_country' => 'userCountry'
        ];

        $filters = [];
        foreach($filterFields as $filterKey => $filterField) {
            $filters[$filterField] = !empty($filter[$filterKey]) ? $filter[$filterKey] : '';
        }

        $gameObj = new Games();
        $gamesCatalog = null;
        $try = 0; // Trying 3 times

        do {
            $gamesCatalog = $gameObj->getGamesData($filters['merchant'], $filters['category'], $filters['orderBy']);

            if (is_object($gamesCatalog)) {
                break;
            } else {
                Games::DropCache(false);
            }

        } while ($try++ < 3);

        if (empty($gamesCatalog)) {
            throw new \Exception('Empty games catalog');
        }

        return self::fillGamesList((array) $gamesCatalog, $filters);
    }

    public static function fillGamesList(array $gamesCatalog, array $filters):array {
        $itemList = [];

        if (!is_array($gamesCatalog)) {
            throw new \Exception('Empty games catalog');
        }

        foreach ($gamesCatalog['categories'] as $id => $category) {
            $category['menuId'] = preg_replace('/[^a-z0-9]/i', '', strtolower($category['Name']['en']));
            $itemList[] = $category;
        }
        $gamesCatalog['categories'] = $itemList;

        $itemList = array();
        if (is_array($gamesCatalog['merchants'])) foreach ($gamesCatalog['merchants'] as $merchant) {
            $merchant['ID'] = '' . $merchant['ID'];
            $merchant['menuId'] = preg_replace('/[^a-z0-9]/i', '', strtolower($merchant['Name']));
            $itemList[$merchant['ID']] = $merchant;
        }
        $gamesCatalog['merchants'] = $itemList;

        $countriesRestrictions = array();
        if (!empty($gamesCatalog['countriesRestrictions']) && !empty($filters['userCountry'])) foreach ($gamesCatalog['countriesRestrictions'] as $country) {
            $countriesRestrictions[$country['ID']] = array_flip($country['Countries']);
        }

        $itemList = array();
        foreach ($gamesCatalog['games'] as $id => $game) {
            $launchCode = explode('/', $game['Url'], 2)[1];
            $game['LaunchCode'] = str_replace(':', '--', $launchCode);
            $itemList[] = $game;
        }
        $gamesCatalog['games'] = $itemList;

        return $gamesCatalog;
    }

    /**
     * Returns game and category list
     *
     * @protected
     * @method get_list
     * @param array $request
     * @param array $query
     * @return array|mixed
     */
    protected function get_list($request, $query)
    {
        $cacheLifetime = 3600;
        $filter = self::getGamesFilter($query);

        if (\eGamings\WLC\GZ::canUse($filter, $query)) {
            $gz = \eGamings\WLC\GZ::getGZ();

            if ($gz !== '') {
                header('Content-Encoding: gzip');
                header('Content-Type: application/json; encoding=utf-8');
                header('Content-Length: '. strlen($gz));
                header('Cache-control: public, max-age=' . $cacheLifetime);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheLifetime) . ' GMT');
                print $gz;
                exit;
            }
        }

        $gamesCatalog = self::getGamesCatalog($filter);
        if (!$gamesCatalog) {
            header('Cache-Control: must-revalidate, no-cache, no-store');
            throw new ApiException(_('Games catalog is temporary unavailable'));
        }

        //Enable game caching for 1 hour
        header('Cache-control: public, max-age=' . $cacheLifetime);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheLifetime) . ' GMT');
        return $gamesCatalog;
    }

    public static function buildGamesList(): string
    {
        $filter = self::getGamesFilter([]);

        if (\eGamings\WLC\GZ::canUse($filter, ['slim' => true])) {
            return \eGamings\WLC\GZ::getJsonSlim();
        } else {
            return json_encode(self::getGamesCatalog($filter), JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Returns params launch game
     *
     * @protected
     * @method get_item
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {ApiException}
     */
    protected function get_item($request, $query)
    {
        if (empty($query['launchCode'])) {
            throw new ApiException(_('Empty launch code'), 400);
        }

        if (empty($query['merchantId'])) {
            throw new ApiException(_('Empty merchant identifier'), 400);
        }

        $user = new \eGamings\WLC\User();

        $isDemo = !empty($query['demo']) && $query['demo'] == '1';

        if (_cfg('requiredFieldsList') && !$isDemo) {
            $user->checkRequiredFields(_cfg('requiredFieldsList'));
        }

        if ((bool) _cfg('fastRegistrationWithoutBets') && $user->userData->email_verified == 0 && !$isDemo) {
            throw new ApiException(_('Confirm your email to start games'), 400);
        }

        $g = Front::Games();


        $filter = self::getGamesFilter($query);
        $merchantId = $filter['merchant'] = $query['merchantId'];
        $subMerchantId = 0;
        $gamesCatalog = self::getGamesCatalog($filter);

        if (empty($gamesCatalog) || empty($gamesCatalog['games'])) {
            throw new ApiException(_('Empty games catalog'), 400);
        }

        $gameCodeFound = false;
        foreach ($gamesCatalog['games'] as $game) {
            if (
                $game['LaunchCode'] == $query['launchCode']
                && (
                    $game['MerchantID'] == $merchantId
                    || $game['SubMerchantID'] == $merchantId
                )
            ) {
                $merchantId = $game['MerchantID'];
                $subMerchantId = $game['SubMerchantID'];
                $gameCodeFound = true;
                break;
            }
        }

        if (!$gameCodeFound) {
            throw new ApiException(_('Unknown game launch code'), 400);
        }

        if ($subMerchantId && empty($gamesCatalog['merchants'][$merchantId])) {
            $merchantId = $subMerchantId;
        }

        $merchantName = !empty($gamesCatalog['merchants'][$merchantId]['menuId']) ?
            $gamesCatalog['merchants'][$merchantId]['menuId'] : 'unknown_merchant';

        unset($gamesCatalog);

        if (!empty($request['returnUrl'])) {
            $returnUrlInfo = parse_url($request['returnUrl']);
            if (is_array($returnUrlInfo) && empty($returnUrlInfo['scheme'])) {
                $returnUrlInfo['scheme'] = (empty($_SERVER['HTTPS'])) ? 'http' : 'https';
                $returnUrlInfo['host'] = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] .
                    (!empty($_SERVER['PORT']) ? ':' . $_SERVER['SERVER_PORT'] : '');
                $request['returnUrl'] = System::build_url($returnUrlInfo);

            }
            $_SERVER['HTTP_REFERER'] = $request['returnUrl'];
        }

        $launchCode = str_replace('--', ':', $query['launchCode']);

        $platform = (!empty($query['platform'])) ? $query['platform'] : '';
        $wallet = !empty($request['wallet']) ? (int)$request['wallet'] : null;
        $currency = $request['currency'] ?: null;

        $game_params = $g->LaunchHTML($merchantId, $launchCode, $isDemo, true, $platform, $wallet, $currency);
        $game_script = '';
        $game_html = '';
        $config = null;

        if (is_array($game_params) && !empty($game_params['error'])) {
            throw new ApiException($game_params['error'], 400);
        } else {
            $game_params_array = is_array($game_params) ? $game_params : json_decode($game_params, true);
            if (is_array($game_params_array)) {
                $game_params = $game_params_array;

                $gameLaunchParams = array(
                    'merchantId' => $merchantId,
                    'merchant' => $merchantName,
                    'config' => !empty($game_params['config']) ? $game_params['config'] : [],
                    'gameHtml' => !empty($game_params['gameHtml']) ? $game_params['gameHtml'] : '',
                    'gameScript' => !empty($game_params['gameScript']) ? $game_params['gameScript'] : '',
                    'mobilePlatform' => _cfg('mobileDetected')
                );
            } else {
                if (strlen($game_params)) {
                    $doc = new \DOMDocument();

                    //--- JS part
                    if ($doc->loadHTML($game_params)) {
                        self::removeElementsByTagName('span', $doc);
                        self::removeElementsByTagName('iframe', $doc);
                        $html = $doc->saveHTML();
                        $game_script = preg_replace('/^<!DOCTYPE.+?>/', '',
                            str_replace(array('<html>', '</html>', '<body>', '</body>'), '',
                                $html));
                    }

                    //--- HTML part
                    $doc = new \DOMDocument();

                    if ($doc->loadHTML($game_params)) {
                        self::removeElementsByTagName('script', $doc);
                        $html = $doc->saveHTML();
                        $game_html = preg_replace('/^<!DOCTYPE.+?>/', '',
                            str_replace(array('<html>', '</html>', '<body>', '</body>'), '',
                                $html));
                    }
                }

                $gameLaunchParams = array(
                    'merchantId' => $merchantId,
                    'merchant' => $merchantName,
                    'config' => $config,
                    'gameHtml' => $game_html,
                    'gameScript' => $game_script
                );
            }
        }

        return $gameLaunchParams;
    }

    /**
     * @SWG\Get(
     *     path="/games",
     *     description="Returns game and category list or game info depending on query params",
     *     tags={"games"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="lastGames",
     *         in="query",
     *         type="boolean",
     *         description="Last games",
     *     ),
     *     @SWG\Parameter(
     *         name="category",
     *         in="query",
     *         type="string",
     *         description="Game category",
     *     ),
     *     @SWG\Parameter(
     *         name="order_by",
     *         in="query",
     *         type="string",
     *         description="Game catalog order",
     *     ),
     *     @SWG\Parameter(
     *         name="platform",
     *         in="query",
     *         type="string",
     *         description="Platform",
     *     ),
     *     @SWG\Parameter(
     *         name="launchCode",
     *         in="query",
     *         type="string",
     *         description="Launch code",
     *     ),
     *     @SWG\Parameter(
     *         name="merchant",
     *         in="query",
     *         type="string",
     *         description="Merchant ID",
     *     ),
     *     @SWG\Parameter(
     *         name="demo",
     *         in="query",
     *         type="boolean",
     *         description="Demo",
     *     ),
     *     @SWG\Parameter(
     *         name="slim",
     *         in="query",
     *         type="boolean",
     *         description="Slim game list",
     *     ),
     *     @SWG\Parameter(
     *         name="returnUrl",
     *         in="body",
     *         type="string",
     *         description="Return URL",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns games list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/GameList"
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
     *     path="/games/launch",
     *     description="Returns game laucn params",
     *     tags={"games"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="launchCode",
     *         type="string",
     *         in="query",
     *         description="Game launch code",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="merchantId",
     *         type="string",
     *         in="query",
     *         description="Game merchant id",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="wallet",
     *         type="integer",
     *         in="query",
     *         description="Wallet number",
     *     ),
     *     @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         in="query",
     *         description="Currency ISO code",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns game",
     *         @SWG\Schema(
     *             ref="#/definitions/Game"
     *         ),
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
     * Returns game and category list or game info depending on query params
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @return {array|mixed}
     * @throws ApiException
     */
    public function get($request, $query, $params)
    {
        if (!empty($query['lastGames'])) { //url /games
            $user_id = (int)Front::User('id');
            $platform = (!empty($query['platform'])) ? $query['platform'] : '';
            $limit = !empty($query['limit']) ? (int)$query['limit'] : 0;

            if (!$user_id) {
                throw new ApiException('User is not authorized', 401);
            }

            $g = Front::Games();
            $games = $g->getLatestGames($user_id, $platform, $limit);

            if (is_array($games)) {
                return array_values($games);
            }

            throw new ApiException($games, 400);
        }

        if (!empty($query['launchCode'])) { //url /games
            $this->checkAcceptCurrentTermsOfServiceForStartGame();
            return $this->get_item($request, $query);
        }

        return $this->get_list($request, $query); //url /games/launch
    }

    /**
     * Get games images with filter
     *
     * @param array $params Filter params
     * @return array Assoc array of game => image
     */
    public static function getGameImages($params = []): array
    {
        $images = Cache::result('api-wins-gameimages', static function () use ($params) {
            $data = self::getGamesCatalog($params);
            if (!is_array($data) || empty($data['games'])) {
                return [];
            }
            $images = array_reduce($data['games'], function ($result, $game) {
                if (!empty($game['Image'])) {
                    $result[$game['MerchantID'] . ':' . pathinfo($game['Image'], PATHINFO_BASENAME)] = $game['Image'];
                    if ((int)$game['SubMerchantID'] > 0) {
                        $result[$game['SubMerchantID'] . ':' . pathinfo($game['Image'], PATHINFO_BASENAME)] = $game['Image'];
                    }
                }
                return $result;
            }, []);
            return $images;
        }, 60 * 5, $params);

        return $images;
    }

    /**
     * @return void
     * @throws ApiException
     */
    private function checkAcceptCurrentTermsOfServiceForStartGame(): void
    {
        if (
            !empty(_cfg('termsOfService')) &&
            !empty($_SESSION['user']) &&
            !User::isCurrentUserAcceptCurrentTermsOfService() &&
            empty($query['demo'])
        ) {
            throw new ApiException('You need to accept terms of service');
        }
    }
}
