<?php
namespace eGamings\WLC\RestApi;

use Egamings\UserDataMasking\UserDataMasking;
use eGamings\WLC\Cache;
use eGamings\WLC\Config;
use eGamings\WLC\Games;
use eGamings\WLC\RestApi\GamesResource;
use eGamings\WLC\User;
use eGamings\WLC\Utils;

/**
 * @SWG\Tag(
 *     name="wins",
 *     description="Wins"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Win",
 *     description="User win",
 *     type="object",
 *     @SWG\Property(
 *         property="Game",
 *         type="string",
 *         description="Name of the game"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Description of the game"
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="string",
 *         description="Path to game image"
 *     ),
 *     @SWG\Property(
 *         property="Url",
 *         type="string",
 *         description="Path to game"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Name of the winner"
 *     ),
 *     @SWG\Property(
 *         property="LastName",
 *         type="string",
 *         description="Last name of the winner"
 *     ),
 *     @SWG\Property(
 *         property="Email",
 *         type="string",
 *         description="Email of the winner"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         description="Currency of the winner"
 *     ),
 *     @SWG\Property(
 *         property="CountryIso2",
 *         type="string",
 *         description="Country of the winner (iso 2)"
 *     ),
 *     @SWG\Property(
 *         property="CountryIso3",
 *         type="string",
 *         description="Country of the winner (iso 3)"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="number",
 *         description="Amount of the win"
 *     ),
 *     @SWG\Property(
 *         property="AmountEUR",
 *         type="number",
 *         description="Amount of the win in euro"
 *     ),
 *     @SWG\Property(
 *         property="Date",
 *         type="string",
 *         description="Date and time of the win"
 *     ),
 *     @SWG\Property(
 *         property="Merchant",
 *         type="string",
 *         description="Name of the game merchant"
 *     ),
 *     @SWG\Property(
 *         property="GameCode",
 *         type="string",
 *         description="Launch code of the game"
 *     ),
 *     @SWG\Property(
 *         property="Mobile",
 *         type="boolean",
 *         description="Mobile game"
 *     ),
 *     @SWG\Property(
 *         property="MerchantID",
 *         type="string",
 *         description="Merchant id of the game"
 *     ),
 *     @SWG\Property(
 *         property="LaunchCode",
 *         type="string",
 *         description="Game launch code"
 *     ),
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Unique id"
 *     )
 * )
 */

/**
 * @class WinResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Games
 */
class WinResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/wins",
     *     description="Returns lists last wins",
     *     tags={"wins"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="merchant",
     *         type="integer",
     *         in="query",
     *         description="Filter by merchant"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         type="integer",
     *         in="query",
     *         description="Items limit (default: 3)"
     *     ),
     *     @SWG\Parameter(
     *         name="min",
     *         type="number",
     *         in="query",
     *         description="Filter by minimum amount (default: 50)"
     *     ),
     *      @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         in="query",
     *         description="Currency"
     *     ),
     *      @SWG\Parameter(
     *         name="single",
     *         type="boolean",
     *         in="query",
     *         description="Single win"
     *     ),
     *     @SWG\Parameter(
     *         name="slim",
     *         type="boolean",
     *         in="query",
     *         description="Slim response"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns validation result",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Win"
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
     * Returns lists last wins
     * (default count is 3)
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     */
    public function get($request, $query, $params)
    {
        $params = array(
            'merchant' => isset($query['merchant']) ? (int)$query['merchant'] : 0,
        	'limit' => (isset($query['limit']) && (int)$query['limit']) ? (int)$query['limit'] : 3,
        	'min' => (isset($query['min']) && (int)$query['min'] > 0) ? (int)$query['min'] : 20,
            'currency' => isset($query['currency']) ? $query['currency'] : '',
            'single' => !empty($query['single']) ? true : false,
            'platform' => (_cfg('mobile') || _cfg('mobileDetected')) ? 'mobile' : 'desktop',
            'lang' => _cfg('language')
        );
        $isSlim = isset($query['slim']) ? (bool) $query['slim'] : false;

        $lastWins = Cache::result('api-wins-' . $isSlim, function() use ($params, $isSlim) {
            $G = new Games();
            $wins = $G->getLastWins($params);
            if (!is_array($wins)) {
                $wins = [];
            }

            $gameImages = !$isSlim ? GamesResource::getGameImages(['platform' => $params['platform']]) : [];

            $siteConfig = Config::getSiteConfig();
            $result = [];

            foreach($wins as &$win) {
                $win['ID'] = md5(json_encode($win));
                $win['ScreenName'] = (new UserDataMasking(
                    $siteConfig['MaskTypeForNameAndLastName'] ?? 'none',
                    $win['Name'] ?? '',
                    $win['LastNameOriginal'] ?? '',
                    $win['EmailOriginal'] ?? '')
                )->getScreenName();

                unset($win['EmailOriginal'], $win['LastNameOriginal']);

                if (!$isSlim) {
                    $win['Mobile'] = ($params['platform'] == 'mobile') ? true : false;

                    $urlInfo = explode('/', $win['Url'], 2);

                    $win['GameCode'] = str_replace(':', '--', $urlInfo[1]);
                    $win['MerchantID'] = $urlInfo[0];
                    $win['LaunchCode'] = $win['GameCode'];

                    if (is_array($win['Game'])) {
                        if (!empty($win['Game'][$params['lang']])) {
                            $win['Game'] = $win['Game'][$params['lang']];
                        } else {
                            $win['Game'] = $win['Game']['en'];
                        }
                    }

                    if (!empty($win['Image'])) {
                        $imageNames = [
                            $win['MerchantID'] . ':' . pathinfo($win['Image'], PATHINFO_FILENAME) . '.svg',
                            $win['MerchantID'] . ':' . pathinfo($win['Image'], PATHINFO_FILENAME) . '.jpg',
                            $win['MerchantID'] . ':' . $win['GameID'] . '.svg',
                            $win['MerchantID'] . ':' . $win['GameID'] . '.jpg',
                        ];
                        foreach ($imageNames as $imageName) {
                            if (array_key_exists($imageName, $gameImages)) {
                                $win['Image'] = $gameImages[$imageName];
                                break;
                            }
                        }
                    }

                    $win['Url'] = $win['MerchantID'].'/'.$win['LaunchCode'];
                } else {
                    $name = trim(sprintf('%s %s', $win['Name'], !empty($win['LastName']) ? mb_substr($win['LastName'], 0, 1) . '.' : ''));

                    if (!$name) {
                        if (mb_strpos($win['Email'], '*') === false) {
                            $email_arr = explode('@', trim($win['Email']), 2);
                            $email_box_len = strlen($email_arr[0]);
                            $email_box_halflen = floor($email_box_len / 2);

                            $name = str_pad(substr($email_arr[0], 0, $email_box_halflen), $email_box_len, '*')
                                . (!empty($email_arr[1]) ? '@' . $email_arr[1] : '');
                        } else {
                            $name = $win['Email'];
                        }
                    }

                    $name = Utils::hideStringWithWildcards($name);

                    $win['Name'] = $name ? $name : _('Anonymous');
                    $damnedFields = ['Game', 'Description', 'Url', 'Merchant', 'UserID', 'LastName', 'GameCode',
                                     'Image', 'ExtLogin', 'Email', 'CurrencyID', 'CountryID', 'EmailHash'];

                    foreach($damnedFields as $field) unset($win[$field]);
                }

                $result[] = $win;
            }

            return $result;
        }, 15, $params);

        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 10)); // 10 seconds

        return $lastWins;
    }
}
