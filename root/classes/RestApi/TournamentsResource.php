<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Loyalty\LoyaltyTournamentsResource;
use eGamings\WLC\Front;

/**
 * @SWG\Tag(
 *     name="tournaments",
 *     description="Tournaments"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Tournament",
 *     description="Tournament",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Tournament ID"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Tournament name"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Tournament description"
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="string",
 *         description="Path to tournament image"
 *     ),
 *     @SWG\Property(
 *         property="Selected",
 *         type="integer",
 *         enum={0, 1},
 *         description="User subscription for the tournament"
 *     ),
 *     @SWG\Property(
 *         property="Qualified",
 *         type="integer",
 *         description="The qualified user to participate in the tournament"
 *     ),
 *     @SWG\Property(
 *         property="WinnerBy",
 *         type="string",
 *         enum={"wins", "bets", "turnovers"},
 *         description="Victory conditions the tournament"
 *     ),
 *     @SWG\Property(
 *         property="PointsTotal",
 *         type="string",
 *         description="User points in the tournament"
 *     ),
 *     @SWG\Property(
 *         property="PointsLimit",
 *         type="string",
 *         description="The maximum winnings"
 *     ),
 *     @SWG\Property(
 *         property="FeeType",
 *         type="string",
 *         enum={"balance", "loyalty"},
 *         description="Type of fee to participate in the tournament"
 *     ),
 *     @SWG\Property(
 *         property="FeeAmount",
 *         type="object",
 *         description="Amount of fee to participate in the tournament",
 *         example={"EUR": "100", "Currency": "100"}
 *     ),
 *     @SWG\Property(
 *         property="Qualification",
 *         type="string",
 *         description="The qualification to participate in the tournament"
 *     ),
 *     @SWG\Property(
 *         property="BetMin",
 *         type="object",
 *         description="Minimum bet",
 *         example={"EUR": "300"}
 *     ),
 *     @SWG\Property(
 *         property="BetMax",
 *         type="string",
 *         description="Maximum bet",
 *         example={"EUR": "300"}
 *     ),
 *     @SWG\Property(
 *         property="Repeat",
 *         type="string",
 *         enum={"once", "1 day", "1 week", "2 weeks", "1 month"},
 *         description="The periodicity of the tournament"
 *     ),
 *     @SWG\Property(
 *         property="Target",
 *         type="string",
 *         enum={"balance", "loyalty"},
 *         description="The reward of the tournament"
 *     ),
 *     @SWG\Property(
 *         property="Type",
 *         type="string",
 *         enum={"absolute", "relative"},
 *         description="Division of the awards of the tournament"
 *     ),
 *     @SWG\Property(
 *         property="Value",
 *         type="string",
 *         description="The award amount",
 *         example="1000"
 *     ),
 *     @SWG\Property(
 *         property="Starts",
 *         type="string",
 *         description="Tournament start date",
 *         example="2017-03-03"
 *     ),
 *     @SWG\Property(
 *         property="Ends",
 *         type="string",
 *         description="Tournament end date",
 *         example="2017-03-05"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         description="Status of the tournament",
 *         enum={"0", "1", "-50", "-95", "-99", "99", "100"},
 *         description="Tournament status (0 - Selected, 1 - Qualified, -50 - Deleted, -95 - Deactivated, -99 - Canceled, 99 - Ending, 100 - Ended)"
 *     ),
 *     @SWG\Property(
 *         property="RemainingTime",
 *         type="integer",
 *         description="The number of seconds before the end of the tournament",
 *         example="133679"
 *     ),
 *     @SWG\Property(
 *         property="CurrentTime",
 *         type="integer",
 *         description="Current time in seconds",
 *         example="1500989511"
 *     ),
 *     @SWG\Property(
 *         property="TotalFounds",
 *         type="object",
 *         description="The award amount",
 *         example={"EUR": "500", "Currency": "500"}
 *     ),
 *     @SWG\Property(
 *         property="WinningSpread",
 *         type="object",
 *         description="Division of award parts",
 *         example={"Percent": {"75", "15", "10"}, "EUR": {"750", "150", "100"}, "Currency": {"750", "150", "100"}}
 *     ),
 *     @SWG\Property(
 *         property="Terms",
 *         type="string",
 *         description="Tournament terms"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TournamentInHistory",
 *     description="Tournament in history",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Tournament ID"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Tournament name"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Tournament description"
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="string",
 *         description="Path to tournament image"
 *     ),
 *     @SWG\Property(
 *         property="Points",
 *         type="string",
 *         description="Tournament points",
 *         example="0.00"
 *     ),
 *     @SWG\Property(
 *         property="Start",
 *         type="string",
 *         description="Date of subscription for the tournament",
 *         example="2017-07-25 13:31:36"
 *     ),
 *     @SWG\Property(
 *         property="End",
 *         type="string",
 *         description="The end date of the tournament",
 *         example="2017-07-25 13:31:36"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="integer",
 *         enum={"-99", "100"},
 *         description="Tournament status (-99 - Canceled, 100 - Ended)"
 *     ),
 *     @SWG\Property(
 *         property="Place",
 *         type="string",
 *         description="User place in the tournament",
 *         example="4"
 *     ),
 *     @SWG\Property(
 *         property="Win",
 *         type="string",
 *         description="The reward in the tournament"
 *     ),
 *     @SWG\Property(
 *         property="BetsCount",
 *         type="string",
 *         description="Count of the bets",
 *         example="20"
 *     ),
 *     @SWG\Property(
 *         property="WinsCount",
 *         type="string",
 *         description="Count of the wins",
 *         example="3"
 *     ),
 *     @SWG\Property(
 *         property="BetsAmount",
 *         type="string",
 *         description="The amount of betting",
 *         example="20.00"
 *     ),
 *     @SWG\Property(
 *         property="WinsAmount",
 *         type="object",
 *         description="The amount of winning bets",
 *         example="20.00"
 *     ),
 *     @SWG\Property(
 *         property="StatusText",
 *         type="string",
 *         description="Translated status"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TournamentTop",
 *     description="Top participants of the tournament",
 *     type="object",
 *     @SWG\Property(
 *         property="results",
 *         type="array",
 *         @SWG\Items(
 *             @SWG\Property(
 *                 property="IDUserPlace",
 *                 type="string",
 *                 description="User place",
 *                 example="1"
 *             ),
 *             @SWG\Property(
 *                 property="IDUser",
 *                 type="string",
 *                 description="User id",
 *                 example="12345"
 *             ),
 *             @SWG\Property(
 *                 property="Points",
 *                 type="string",
 *                 description="User tournament points",
 *                 example="12345"
 *             ),
 *             @SWG\Property(
 *                 property="Login",
 *                 type="string",
 *                 description="User login"
 *             ),
 *             @SWG\Property(
 *                 property="FirstName",
 *                 type="string",
 *                 description="User first name"
 *             ),
 *             @SWG\Property(
 *                 property="LastName",
 *                 type="string",
 *                 description="User last name"
 *             )
 *         )
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TournamentUserResults",
 *     description="User stats in the tournament",
 *     type="object",
 *     @SWG\Property(
 *         property="result",
 *         type="object",
 *         @SWG\Property(
 *             property="ID",
 *             type="string",
 *             description="The user ID in the tournament",
 *             example="12345"
 *         ),
 *         @SWG\Property(
 *             property="IDClient",
 *             type="string",
 *             description="Loyalty client id"
 *         ),
 *         @SWG\Property(
 *             property="IDUser",
 *             type="string",
 *             description="User id"
 *         ),
 *         @SWG\Property(
 *             property="BetsCount",
 *             type="string",
 *             description="Count of the bets",
 *             example="20"
 *         ),
 *         @SWG\Property(
 *             property="WinsCount",
 *             type="string",
 *             description="Count of the wins",
 *             example="3"
 *         ),
 *         @SWG\Property(
 *             property="BetsAmount",
 *             type="string",
 *             description="The amount of betting",
 *             example="20.00"
 *         ),
 *         @SWG\Property(
 *             property="WinsAmount",
 *             type="object",
 *             description="The amount of winning bets",
 *             example="20.00"
 *         ),
 *         @SWG\Property(
 *             property="Points",
 *             type="string",
 *             description="Tournament points",
 *             example="0.00"
 *         ),
 *         @SWG\Property(
 *             property="Status",
 *             type="integer",
 *             enum={"-99", "100"},
 *             description="Tournament status (-99 - Canceled, 100 - Ended)"
 *         ),
 *         @SWG\Property(
 *             property="Place",
 *             type="string",
 *             description="User place in the tournament",
 *             example="4"
 *         ),
 *         @SWG\Property(
 *             property="Win",
 *             type="string",
 *             description="The reward in the tournament"
 *         ),
 *         @SWG\Property(
 *             property="AddDate",
 *             type="string",
 *             description="Date of subscription for the tournament",
 *             example="2017-07-25 13:31:36"
 *         ),
 *         @SWG\Property(
 *             property="EndDate",
 *             type="string",
 *             description="The end date of the tournament",
 *             example="2017-07-25 13:31:36"
 *         ),
 *         @SWG\Property(
 *             property="Balance",
 *             type="string",
 *             description="User loyaty points",
 *             example="3500.0000"
 *         ),
 *         @SWG\Property(
 *             property="IDLoyalty",
 *             type="string",
 *             description="User loyaty id",
 *             example="12345"
 *         ),
 *         @SWG\Property(
 *             property="ExRate",
 *             type="string",
 *             description="User currency rate",
 *             example="1.0000000"
 *         ),
 *         @SWG\Property(
 *             property="Qualification",
 *             type="string",
 *             description="The qualification to participate in the tournament",
 *             example="10"
 *         )
 *     )
 * )
 */

/**
 * @class TournamentsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Loyalty\LoyaltyTournamentsResource
 */
class TournamentsResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/tournaments",
     *     description="Returns tournaments list",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *         ref="#/parameters/currency"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Tournaments list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Tournament"
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
     *     path="/tournaments/active",
     *     description="Returns active tournaments",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Active tournaments",
     *         @SWG\Schema(
     *             type="object",
     *             example={9958: true}
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
     *     path="/tournaments/history",
     *     description="Returns tournaments history",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Tournaments history",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/TournamentInHistory"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Tournament not found",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
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
     *     path="/tournaments/{id}",
     *     description="Returns tournament by id",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *         ref="#/parameters/currency"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Tournament",
     *         @SWG\Schema(
     *             ref="#/definitions/Tournament"
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
     *     path="/tournaments/{id}/top",
     *     description="Returns the top participants of the tournament",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *      @SWG\Parameter(
     *         name="limit",
     *         type="integer",
     *         in="query",
     *     ),
     *      @SWG\Parameter(
     *         name="start",
     *         type="integer",
     *         in="query",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Tournament top",
     *         @SWG\Schema(
     *             ref="#/definitions/TournamentTop"
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
     *     path="/tournaments/{id}/stats",
     *     description="Returns statistics of the tournament",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Tournament stats",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="top",
     *                 ref="#/definitions/TournamentTop"
     *             ),
     *             @SWG\Property(
     *                 property="tournament",
     *                 ref="#/definitions/Tournament"
     *             ),
     *             @SWG\Property(
     *                 property="user",
     *                 ref="#/definitions/TournamentUserResults"
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
     *     path="/tournaments/{id}/user",
     *     description="Returns user statistics of the tournament",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User stats",
     *         @SWG\Schema(
     *             ref="#/definitions/TournamentUserResults"
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
     * Returns tournaments list
     *
     * @public
     * @method get
     * @param {array|mixed} $request
     * @param {array|mixed} $query
     * @param {array|mixed} $params
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $result = [];
        $fundist_uid = Front::User('fundist_uid');
        $tournamentId = !empty($params['id']) ? $params['id'] : '';
        $tournamentAction = !(empty($params['action'])) ? $params['action'] : '';
        $tournamentType =  !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : 'all');
        $tournamentCurrency = !empty($query['currency']) ? $query['currency'] : '';

        if (!$fundist_uid && empty($tournamentCurrency)) {
            $tournamentCurrency = _cfg('defaultCurrency');
        }

        if ($tournamentId == '') {
            switch($tournamentType) {
                case 'history':
                    $history_result = LoyaltyTournamentsResource::TournamentsHistory();
                    if (isset($history_result['result'])) {
                        $result = $history_result['result'];
                    } else {
                        throw new ApiException(_('Tournaments history error'), 404);
                    }
                    break;

                case 'userstats':
                    if (!$fundist_uid) {
                         throw new ApiException(_('must_login'), 401);
                    }

                    $tournaments = [];
                    $history_result = LoyaltyTournamentsResource::TournamentsHistory();
                    if (is_array($history_result['result'])) {
                        $tournaments = $history_result['result'];
                    }

                    $result = [
                        'played' => 0,
                        'money' => 0,
                        'wins' => 0
                    ];

                    if (is_array($tournaments)) {
                        foreach($tournaments as $tournament) {
                            if ($tournament['Status'] != '100') {
                                continue;
							}
                            $result['played'] += 1;

                            $tournamentWin = floatval($tournament['Win']);
                            if ($tournamentWin > 0) {
                                $result['money'] += $tournamentWin;
                            }

                            $tournamentPlace = intval($tournament['Place']);
                            if ($tournamentPlace > 0 && $tournamentPlace < 4) {
                                $result['wins'] += 1;
                            }
                        }
                    }
                	break;

                case 'active':
                    $tournaments = LoyaltyTournamentsResource::TournamentsRegistered();
                    if (!is_array($tournaments)) {
                    	$result = [];
                    } else {
                    	$result = array_values($tournaments);
                    }

                    break;

                case 'all':
                    try {
                        $result = LoyaltyTournamentsResource::TournamentsList($tournamentCurrency);
                    } catch (\Exception $ex) {
                        throw new ApiException(_('Tournaments list not available'), 400, $ex, [$ex->getMessage()]);
                    }
                    break;

                default:
                    throw new ApiException(_('Tournaments type not found'), 404);
                    break;
            }
        } else {
            switch($tournamentAction) {
                case 'top':
                    $allowVars = [];
                    if (array_key_exists('limit', $query)) {
                        $allowVars['Limit'] = $query['limit'];
                    }
                    if (array_key_exists('start', $query)) {
                        $allowVars['Start'] = $query['start'];
                    }
                    $result = LoyaltyTournamentsResource::TournamentWidgetsTop($tournamentId, $allowVars);
                    break;

                case 'user':
                    $result = LoyaltyTournamentsResource::TournamentWidgetsUser($tournamentId);
                    break;

                case 'stats':
                    $result = LoyaltyTournamentsResource::TournamentStatistics($tournamentId);
                    break;

                default:
                    $result = LoyaltyTournamentsResource::TournamentGet($tournamentId, $tournamentCurrency);
                    break;
            }

            if (!$result) {
                throw new ApiException(_('Tournament not found'), 404);
            }
        }

        return $result;
    }

    /**
     * @SWG\Post(
     *     path="/tournaments/{id}",
     *     description="Subscribe to tournament",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"Selected"},
     *             @SWG\Property(
     *                 property="Selected",
     *                 type="integer",
     *                 description="Selected = 1, to subscribe"
     *             ),
     *             @SWG\Property(
     *                 property="PromoCode",
     *                 type="string",
     *                 description="Promo code"
     *             ),
     *             @SWG\Property(
     *                  property="wallet",
     *                  type="integer",
     *                  description="Wallet number",
     *                  example=443266573
     *              ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="Selected",
     *                 type="integer",
     *                 description="Subscription status",
     *                 example=1
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
     * Subscribe to tournament
     *
     * @public
     * @method post
     * @param {array|mixed} $request - Request parameters
     * @param {array|mixed} $query - Query parameters
     * @param {array|mixed} $params - Route parameters
     * @return {array|mixed} - Select status
     * @throws {ApiException}
     */
    function post($request, $query, $params)
    {
    	$selectValue = (isset($request['Selected'])) ? $request['Selected'] > 0 : false;
        $promocode = !empty($request['PromoCode']) ? $request['PromoCode'] : '';
        $wallet = !empty($request['wallet']) ? (int)$request['wallet'] : null;

        $result = LoyaltyTournamentsResource::TournamentsSelect(
            $params['id'],
            $selectValue,
            $promocode,
            $wallet
        );

        if (!empty($result['error'])) {
            throw new ApiException($result['error'], 400);
        }

        if (isset($result['result']['Status']) && $result['result']['Status'] === 1) {
        	return ['Selected' => $selectValue ? 1 : 0];
        }

        throw new ApiException(_('Tournaments subscribe error'), 400);
    }

    /**
     * @SWG\Delete(
     *     path="/tournaments/{id}",
     *     description="Unsubscribe from tournament",
     *     tags={"tournaments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="Selected",
     *                 type="integer",
     *                 description="Subscription status",
     *                 example=0
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
     * Unsubscribe to tournament
     *
     * @public
     * @method delete
     * @param {array|mixed} $request - Request parameters
     * @param {array|mixed} $query - Query parameters
     * @param {array|mixed} $params - Route parameters
     * @return {array|mixed} - Select status
     * @throws {ApiException}
     */
    function delete($request, $query, $params)
    {
        $result = LoyaltyTournamentsResource::TournamentsSelect($params['id'], false);
        if (!empty($result['error'])) {
            throw new ApiException($result['error'], 400);
        }

        if ($result['result']['Status'] === 1) {
            return ['Selected' => 0];
        }

        throw new ApiException(_('Tournaments subscribe error'), 400);
    }
}
