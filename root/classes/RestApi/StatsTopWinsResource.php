<?php 
namespace eGamings\WLC\RestApi;

use Egamings\UserDataMasking\UserDataMasking;
use eGamings\WLC\Config;
use eGamings\WLC\User;
use eGamings\WLC\Utils;

/**
 * @SWG\Tag(
 *     name="top wins",
 *     description="Top wins"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TopWin",
 *     description="Top win",
 *     type="object",
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
 *         description="Name of the merchant"
 *     ),
 *     @SWG\Property(
 *         property="GameCode",
 *         type="string",
 *         description="Launch code of the game"
 *     ),
 *     @SWG\Property(
 *         property="GameName",
 *         type="string",
 *         description="Name of the game"
 *     ),
 *     @SWG\Property(
 *         property="GameDescription",
 *         type="string",
 *         description="Description of the game"
 *     ),
 *     @SWG\Property(
 *         property="ScreenName",
 *         type="string",
 *         description="Name of the winner for displaying"
 *     )
 * )
 */

/**
 * @class StatsTopWinsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class StatsTopWinsResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/stats/topWins",
     *     description="Returns list top wins",
     *     tags={"top wins"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="merchant",
     *         type="integer",
     *         in="query",
     *         description="Merchant id"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         type="integer",
     *         in="query",
     *         description="Limit of wins"
     *     ),
     *     @SWG\Parameter(
     *         name="min",
     *         type="integer",
     *         in="query",
     *         description="Minimum win amount to show"
     *     ),
     *     @SWG\Parameter(
     *         name="startDate",
     *         type="string",
     *         format="date-time",
     *         in="query",
     *         description="Start date of selection. Example: 2017-07-21T17:32:28Z"
     *     ),
     *     @SWG\Parameter(
     *         name="endDate",
     *         type="string",
     *         format="date-time",
     *         in="query",
     *         description="End date of selection. Example: 2017-07-22T17:32:28Z"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of top wins",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/TopWin"
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
     * Returns list top wins
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    public function get($request, $query, $params = [])
    {
        $rv = [];
        $user = new User();
        $url = '/WLCGames/TopWins/?';

        $transactionId = $user->getApiTID($url);
        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [ 'TID' => $transactionId, 'Hash' => $hash ];

        $isSlim = isset($query['slim']) ? (bool) $query['slim'] : false;
        
        if (isset($query['merchant']) && $query['merchant'])
        {
            $params['IDMerchant'] = $query['merchant'];
        }

        if (isset($query['limit']) && $query['limit']) {
            $params['limit'] = 0 + intval($query['limit']);
        }

        if (isset($query['min']) && $query['min']) {
            $params['Minimum'] = $query['min'];
        }

        // add period params to request, if they are set
        $params = array_merge($params, array_intersect_key($query, array_flip(['endDate','startDate'])));

        $url .= '&' . http_build_query($params);
        
        $response = $user->runFundistAPI($url);
        $responseArr = explode(',', $response, 2);
        if (is_numeric($responseArr[0]) && $responseArr[0] == '1') {
            $rv = json_decode($responseArr[1], true);

            if (is_array($rv) && !empty($params['limit'])) {
                array_splice($rv, $params['limit']);
            }
        }
        
        $lang = _cfg('language');
        $siteConfig = Config::getSiteConfig();

        foreach($rv as &$row) {
            $row['ID'] = md5(json_encode($row));
            $row['ScreenName'] = (new UserDataMasking(
                $siteConfig['MaskTypeForNameAndLastName'] ?? '',
                $row['Name'] ?? '',
                $row['LastName'] ?? '',
                $row['Email'] ?? ''
            ))->getScreenName();

            if (!$isSlim) {
                // Set Game Information
                $row['GameName'] = '';
                if (!empty($row['Game'])) {
                    if (is_array($row['Game'])) {
                        $row['GameName'] = !empty($row['Game'][$lang]) ? $row['Game'][$lang] : $row['Game']['en'];
                    } else {
                        $row['GameName'] = $row['Game'];
                    }
                }

                $row['GameDescription'] = '';
                if (!empty($row['Description'])) {
                    if (is_array($row['Description'])) {
                        $row['GameDescription'] = !empty($row['Description'][$lang]) ? $row['Description'][$lang] : $row['Description']['en'];
                    } else {
                        $row['GameDescription'] = $row['Description'];
                    }
                }

                unset($row['Game']);
                unset($row['Description']);
            } else {
                $damnedFields = ['Game', 'Description', 'Url', 'Image', 'Merchant', 'UserID', 'LastName', 'GameCode', 
                                 'Image', 'ExtLogin', 'Email', 'CurrencyID', 'CountryID', 'EmailHash', 'ID'];
                    
                foreach($damnedFields as $field) unset($row[$field]);
            }
        }

        return $rv;
    }
}
