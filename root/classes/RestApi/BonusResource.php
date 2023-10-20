<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Ajax;
use eGamings\WLC\Loyalty\LoyaltyBonusesResource;

/**
 * @SWG\Tag(
 *     name="bonus",
 *     description="Bonuses"
 * )
 */


/**
 * @SWG\Definition(
 *     definition="Bonus",
 *     description="Bonus",
 *     type="object",
 *     @SWG\Property(
 *         property="Active",
 *         type="integer",
 *         enum={0, 1},
 *         description="Activated bonus (0 - not activated, 1 - activated)"
 *     ),
 *     @SWG\Property(
 *         property="AllowCatalog",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Display in catalog"
 *     ),
 *     @SWG\Property(
 *         property="AllowStack",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Allow stack"
 *     ),
 *     @SWG\Property(
 *         property="AmountMax",
 *         type="object",
 *         example={"EUR": "20"},
 *         description="Amount max"
 *     ),
 *     @SWG\Property(
 *         property="AmountMin",
 *         type="object",
 *         example={"EUR": "1"},
 *         description="Amount min"
 *     ),
 *     @SWG\Property(
 *         property="AwardWageringTotal",
 *         type="number",
 *         description="Amount of wagering on all chunks. *For active bonus. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="Balance",
 *         type="string",
 *         description="Winnings on the bonus"
 *     ),
 *     @SWG\Property(
 *         property="Block",
 *         type="string",
 *         example="350.00",
 *         description="Blocked balance"
 *     ),
 *     @SWG\Property(
 *         property="Bonus",
 *         type="number",
 *         description="Bonus"
 *     ),
 *     @SWG\Property(
 *         property="BonusType",
 *         type="string",
 *         enum={"general", "poker"},
 *         description="Type of bonus"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Bonus description"
 *     ),
 *     @SWG\Property(
 *         property="Date",
 *         type="string",
 *         example="2017-07-18 14:07:02",
 *         description="Date of bonus activation"
 *     ),
 *     @SWG\Property(
 *         property="DisableCancel",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Disable сancel"
 *     ),
 *     @SWG\Property(
 *         property="Event",
 *         type="string",
 *         enum={"deposit first", "deposit repeated", "registration", "sign up", "deposit sum", "deposit", "bet sum", "bet", "win sum", "loss sum"},
 *         description="Event to activate the bonus"
 *     ),
 *     @SWG\Property(
 *         property="ExperiencePoints",
 *         type="string",
 *         example="1000.00",
 *         description="Bonus experience points. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="Expire",
 *         type="string",
 *         example="2017-09-01",
 *         description="Expiration date bonus"
 *     ),
 *     @SWG\Property(
 *         property="ExpireAction",
 *         type="string",
 *         enum={"bonus", "win", "winbonus", "all"},
 *         description="Expire action. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="ExpireAmount",
 *         type="string",
 *         example="10559.48",
 *         description="The amount of the expiration of the bonus. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="FreeroundCount",
 *         type="string",
 *         example="100",
 *         description="Bonus freeround count. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="FreeroundWagering",
 *         type="string",
 *         example="2017-09-01",
 *         description="Wagering for free rounds. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="FreeroundWinning",
 *         type="string",
 *         example="2017-09-01",
 *         description="Wagering for free rounds. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="Group",
 *         type="string",
 *         description="Bonus group"
 *     ),
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         example="12345",
 *         description="Bonus ID"
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="object",
 *         example={"en": "/static/images/bonus1.jpg"},
 *         description="Bonus image"
 *     ),
 *     @SWG\Property(
 *         property="Limitation",
 *         type="string",
 *         enum={"winbonus", "winbonusevent", "win", "bonus", "all", "none"},
 *         description="Withdraw limitation (winbonus - win+bonus lock, winbonusevent - win+bonus+event lock, win - win lock, bonus - bonus lock, all - full lock, none - no lock)"
 *     ),
 *     @SWG\Property(
 *         property="LoyaltyPoints",
 *         type="string",
 *         description="15000.00",
 *         description="Bonus loyalty points"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Bonus name"
 *     ),
 *     @SWG\Property(
 *         property="PromoCode",
 *         type="integer",
 *         enum={0, 1},
 *         description="There is a promo code at the bonus or not"
 *     ),
 *     @SWG\Property(
 *         property="PromoCodeUsed",
 *         type="string",
 *         description="Bonus is activated by promo code. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="ReleaseWageringTotal",
 *         type="number",
 *         description="Amount of release wagering on all chunks. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="Selected",
 *         type="integer",
 *         enum={0, 1},
 *         description="Signed for the bonus or not (0 - not signed, 1 - signed)"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"0", "1", "100", "90", "-100", "-99"},
 *         description="Bonus status (0 - signed, 1 - activated, 100 - wagered, 90 - closed at zero balance, -100 - expired, -99 - canceled)"
 *     ),
 *     @SWG\Property(
 *         property="Target",
 *         type="string",
 *         example="balance,experience,freerounds,loyalty"
 *     ),
 *     @SWG\Property(
 *         property="Wagering",
 *         type="number",
 *         description="Current wagering. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="WageringLeft",
 *         type="string",
 *         example="100365.91",
 *         description="How much is left to wager. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="WageringTotal",
 *         type="string",
 *         example="100365.91",
 *         description="Total wagering. *For active bonus."
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="BonusDetails",
 *     description="Bonus details",
 *     type="object",
 *     @SWG\Property(
 *         property="ActivationData",
 *         type="object",
 *         description="Activation data",
 *         @SWG\Property(
 *             property="Activated",
 *             type="string",
 *             example="2017-07-18 14:07:02",
 *             description="Date of bonus activation"
 *         ),
 *         @SWG\Property(
 *             property="AddDate",
 *             type="string",
 *             example="2017-07-18 14:07:02",
 *             description="Creation date of the bonus"
 *         ),
 *         @SWG\Property(
 *             property="BonusAmount",
 *             type="string",
 *             example="31602.00",
 *             description="Balance bonus"
 *         ),
 *         @SWG\Property(
 *             property="BonusAmountEUR",
 *             type="string",
 *             example="31602.00",
 *             description="Balance bonus in euro"
 *         ),
 *         @SWG\Property(
 *             property="BonusAwarded",
 *             type="string",
 *             example="15000.00",
 *             description="Award by bonus"
 *         ),
 *         @SWG\Property(
 *             property="BonusBlockBalance",
 *             type="string",
 *             example="12000.00",
 *             description="Balance is blocked by bonus"
 *         ),
 *         @SWG\Property(
 *             property="ChunkAwardWagering",
 *             type="string",
 *             example="4160.52",
 *             description="Award wagering of current chunk"
 *         ),
 *         @SWG\Property(
 *             property="ChunkAwardWageringTotal",
 *             type="object",
 *             example={1: "200.52", 2: "300", 3: "500"},
 *             description="Award wagering of all chunks"
 *         ),
 *         @SWG\Property(
 *             property="ChunkReleaseWagering",
 *             type="string",
 *             example="320.52",
 *             description="Release wagering of current chunk"
 *         ),
 *         @SWG\Property(
 *             property="ChunkReleaseWageringTotal",
 *             type="object",
 *             example={1: "200.52", 2: "300", 3: "500"},
 *             description="Release wagering of all chunks"
 *         ),
 *         @SWG\Property(
 *             property="ChunkSize",
 *             type="object",
 *             example={1: "3160", 2: "5000", 3: "1600"},
 *             description="Size of all chunks"
 *         ),
 *         @SWG\Property(
 *             property="Currency",
 *             type="string",
 *             example="EUR",
 *             description="Currency"
 *         ),
 *         @SWG\Property(
 *             property="CurrentChunk",
 *             type="string",
 *             example="4",
 *             description="Current chunk. Starts with 0."
 *         ),
 *         @SWG\Property(
 *             property="CurrentChunkBlock",
 *             type="string",
 *             example="4",
 *             description="Current release chunk. Starts with 0."
 *         ),
 *         @SWG\Property(
 *             property="EndDate",
 *             type="string",
 *             description="End bonus date"
 *         ),
 *         @SWG\Property(
 *             property="EventAmount",
 *             type="string",
 *             example="31602.00",
 *             description="Balance bonus"
 *         ),
 *         @SWG\Property(
 *             property="ExperiencePoints",
 *             type="string",
 *             example="1000.00",
 *             description="Bonus experience points"
 *         ),
 *         @SWG\Property(
 *             property="ExpireDate",
 *             type="string",
 *             example="2017-10-12 09:34:04",
 *             description="Expiration date bonus"
 *         ),
 *         @SWG\Property(
 *             property="FreeroundCount",
 *             type="string",
 *             example="100",
 *             description="Bonus freeround count"
 *         ),
 *         @SWG\Property(
 *             property="FreeroundWagering",
 *             type="string",
 *             example="6320.00",
 *             description="Wagering for free rounds"
 *         ),
 *         @SWG\Property(
 *             property="FreeroundWinning",
 *             type="string",
 *             example="5000.00",
 *             description="Winning for free rounds"
 *         ),
 *         @SWG\Property(
 *             property="LoyaltyPoints",
 *             type="string",
 *             description="15000.00",
 *             description="Bonus loyalty points"
 *         ),
 *         @SWG\Property(
 *             property="Wagering",
 *             type="string",
 *             example="100365.91",
 *             description="How much is left to wager"
 *         ),
 *         @SWG\Property(
 *             property="WageringTotal",
 *             type="string",
 *             example="129806.43",
 *             description="Total wagering"
 *         ),
 *         @SWG\Property(
 *             property="Winning",
 *             type="string",
 *             example="10000.00",
 *             description="Winning on a bonus"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="ActivationDays",
 *         type="array",
 *         example={"Mon", "Wed"},
 *         description="Days of a activations",
 *         @SWG\Items(
 *             type="string"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="AllowCatalog",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Display in catalog"
 *     ),
 *     @SWG\Property(
 *         property="AllowStack",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Allow stack"
 *     ),
 *     @SWG\Property(
 *         property="AutoSubscribe",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Auto subscribe"
 *     ),
 *     @SWG\Property(
 *         property="BonusType",
 *         type="string",
 *         enum={"general", "poker"},
 *         description="Type of bonus"
 *     ),
 *     @SWG\Property(
 *         property="Budget",
 *         type="string",
 *         example="1000",
 *         description="Bonus budget"
 *     ),
 *     @SWG\Property(
 *         property="Conditions",
 *         type="object",
 *         description="Bonus conditions",
 *         @SWG\Property(
 *             property="AmountMax",
 *             type="object",
 *             example={"EUR": "1000", "RUB": "30000"}
 *         ),
 *         @SWG\Property(
 *             property="AmountMin",
 *             type="object",
 *             example={"EUR": "1000", "RUB": "30000"}
 *         ),
 *         @SWG\Property(
 *             property="Countries",
 *             type="object",
 *             example={"usa": "1"}
 *         ),
 *         @SWG\Property(
 *             property="Currencies",
 *             type="object",
 *             example={"EUR": "1", "RUB": "1"}
 *         ),
 *         @SWG\Property(
 *             property="Days",
 *             type="object",
 *             example={"2": "1", "3": "1"}
 *         ),
 *         @SWG\Property(
 *             property="Games",
 *             type="object",
 *             @SWG\Property(
 *                 property="Games",
 *                 type="object",
 *                 example={"1664122":"15","60994":"15","1664046":"15"}
 *             ),
 *             @SWG\Property(
 *                 property="Merchants",
 *                 type="object",
 *                 example={"982": "15", "995": "20"}
 *             )
 *         ),
 *         @SWG\Property(
 *             property="Languages",
 *             type="object",
 *             example={"en": "1", "de": "1"}
 *         ),
 *         @SWG\Property(
 *             property="Levels",
 *             type="object",
 *             example={"10": "1", "9": "1"}
 *         ),
 *         @SWG\Property(
 *             property="PaySystems",
 *             type="object",
 *             example={"19": "1", "22": "1"}
 *         ),
 *         @SWG\Property(
 *             property="RegionRestrictType",
 *             type="string",
 *             enum={"0", "1"}
 *         )
 *     ),
 *     @SWG\Property(
 *         property="Credited",
 *         type="string",
 *         example="200.00",
 *         description="Credited by bonus"
 *     ),
 *     @SWG\Property(
 *         property="CreditedTotal",
 *         type="string",
 *         example="200.00",
 *         description="Credited total by bonus"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Bonus description"
 *     ),
 *     @SWG\Property(
 *         property="DisableCancel",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Disable сancel"
 *     ),
 *     @SWG\Property(
 *         property="Ends",
 *         type="string",
 *         example="2017-09-01",
 *         description="Bonus end date"
 *     ),
 *     @SWG\Property(
 *         property="Event",
 *         type="string",
 *         enum={"deposit first", "deposit repeated", "registration", "sign up", "deposit sum", "deposit", "bet sum", "bet", "win sum", "loss sum"},
 *         description="Event to activate the bonus"
 *     ),
 *     @SWG\Property(
 *         property="Expire",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Bonus expired"
 *     ),
 *     @SWG\Property(
 *         property="ExpireAction",
 *         type="string",
 *         enum={"bonus", "win", "winbonus", "all"},
 *         description="Expire action. *For active bonus."
 *     ),
 *     @SWG\Property(
 *         property="Group",
 *         type="string",
 *         description="Bonus group"
 *     ),
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         example="12345",
 *         description="Bonus ID"
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="object",
 *         example={"en": "/static/images/bonus1.jpg"},
 *         description="Bonus image"
 *     ),
 *     @SWG\Property(
 *         property="Limitation",
 *         type="string",
 *         enum={"winbonus", "winbonusevent", "win", "bonus", "all", "none"},
 *         description="Withdraw limitation (winbonus - win+bonus lock, winbonusevent - win+bonus+event lock, win - win lock, bonus - bonus lock, all - full lock, none - no lock)"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Bonus name"
 *     ),
 *     @SWG\Property(
 *         property="MaxBet",
 *         type="string",
 *         description="Max bet"
 *     ),
 *     @SWG\Property(
 *         property="PromoCodes",
 *         type="array",
 *         description="Promo codes",
 *         @SWG\Items(
 *             type="string"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="RemoveAtZeroBalance",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Remove at zero balance"
 *     ),
 *     @SWG\Property(
 *         property="Repeat",
 *         type="string",
 *         example="day",
 *         description="Bonus repeat"
 *     ),
 *     @SWG\Property(
 *         property="Reserved",
 *         type="string",
 *         example="3000.00",
 *         description="Reserved for bonus"
 *     ),
 *     @SWG\Property(
 *         property="Results",
 *         type="object",
 *         description="Bonus results",
 *         @SWG\Property(
 *             property="balance",
 *             type="object",
 *             @SWG\Property(
 *                 property="AwardAllChunks",
 *                 type="string",
 *                 enum={"0", "1"},
 *                 description="0 - in chunks, 1 - all chunks"
 *             ),
 *             @SWG\Property(
 *                 property="AwardOrder",
 *                 type="string",
 *                 enum={"bonus_first", "wager_first"}
 *             ),
 *             @SWG\Property(
 *                 property="AwardWagering",
 *                 type="object",
 *                 example={"COEF": "2"}
 *             ),
 *             @SWG\Property(
 *                 property="BetLevel",
 *                 type="string",
 *                 example="1"
 *             ),
 *             @SWG\Property(
 *                 property="ChunkSize",
 *                 type="object",
 *                 example={"EUR": "50", "RUB": "3160"}
 *             ),
 *             @SWG\Property(
 *                 property="ChunkType",
 *                 type="string",
 *                 enum={"relative", "absolute"}
 *             ),
 *             @SWG\Property(
 *                 property="FreeroundGames",
 *                 type="object",
 *                 example={"NetEnt": {"arabian_sw", "hrblackjackonedk_sw"}}
 *             ),
 *             @SWG\Property(
 *                 property="LimitValue",
 *                 type="object",
 *                 example={"RUB": "200.00", "EUR": "10.00"}
 *             ),
 *             @SWG\Property(
 *                 property="ReleaseWagering",
 *                 type="string"
 *             ),
 *             @SWG\Property(
 *                 property="Target",
 *                 type="string",
 *                 enum={"balance", "experience", "freerounds", "loyalty"}
 *             ),
 *             @SWG\Property(
 *                 property="Type",
 *                 type="string",
 *                 enum={"absolute", "relative"}
 *             ),
 *             @SWG\Property(
 *                 property="Value",
 *                 type="object",
 *                 example={"EUR": "300", "RUB": "120000"}
 *             ),
 *             @SWG\Property(
 *                 property="WageringTo",
 *                 type="string",
 *                 example={"bonus", "sum_bonus_event"}
 *             ),
 *             @SWG\Property(
 *                 property="WageringType",
 *                 type="string",
 *                 example={"absolute", "relative"}
 *             )
 *         ),
 *         @SWG\Property(
 *             property="experience",
 *             type="object",
 *             description="Identical to the balance"
 *         ),
 *         @SWG\Property(
 *             property="freerounds",
 *             type="object",
 *             description="Identical to the balance"
 *         ),
 *         @SWG\Property(
 *             property="loyalty",
 *             type="object",
 *             description="Identical to the balance"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="Starts",
 *         type="string",
 *         description="Bonus start date"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"0", "1", "100", "90", "-100", "-99"},
 *         description="Bonus status (0 - signed, 1 - activated, 100 - wagered, 90 - closed at zero balance, -100 - expired, -99 - canceled)"
 *     ),
 *     @SWG\Property(
 *         property="Terms",
 *         type="string"
 *     )
 * )
 */


/**
 * @class BonusResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Ajax
 * @uses eGamings\WLC\Loyalty\LoyaltyBonusesResource
 */
class BonusResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/bonuses",
     *     description="Returns bonus list.",
     *     tags={"bonus"},
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         type="string",
     *         description="Bonus type",
     *         enum={"all", "history", "active", "inventory"}
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Bonus"
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
     *     path="/bonuses/{id}",
     *     description="Returns bonus info.",
     *     tags={"bonus"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="Bonus id. If it contains a 'code' search will be carried out in the parameter 'code'.",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         in="query",
     *         type="string",
     *         description="Bonus promo-code",
     *         default="FREESPINS"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/BonusDetails"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Returns bonus list
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $query['type'] = !empty($query['type']) ? $query['type'] : 'all';

        if (empty($query['type'])) {
            return [];
        }

        $ajax = new Ajax();
        $bonusType = $query['type'];
        $bonusList = [];
        $bonus = [];

        if (!empty($params['id'])) {

            if ($params['id'] == 'code') {
                $bonus['code'] = !empty($query['code']) ? $query['code'] : '';
            } else {
                $bonus['id'] = $params['id'];
            }
        }

        if (!empty($bonus)) {
            try {
                if($bonusType=='data' && !empty($bonus['id'])) {
                    $result = LoyaltyBonusesResource::BonusData($bonus['id']);
                } else {
                    $result = LoyaltyBonusesResource::BonusGet($bonus);
                }

                if (is_string($result)) {
                    throw new \Exception($result);
                }
            } catch (\Exception $ex) {
                $message = $ex->getMessage();
                throw new ApiException(
                  $message === 'No bonus' ?
                    _('Bonus not found') :
                    _('Bonus not found') . ': ' . $message
                  , 404);
            }

            if (!$result) {
                throw new ApiException(_('Bonus not found'), 404);
            }
            return $result;
        }

        try {
	        switch ($bonusType) {
                case 'cancelInfo':
                    if (!isset($query['lbid'])) {
                        throw new ApiException(_('LBID Not found'), 404);
                    }

                    $result = LoyaltyBonusesResource::BonusesCancelInfo($query['lbid']);
                    return $result;
	            case 'history':
	                $bonusList = $ajax->bonus_history();
	                $bonusList = json_decode($bonusList, 1);
	                break;

                case 'inventory':
	            case 'active':
	            default:
	                $bonusList = $ajax->Bonus($query);

                    /* if bonus['SubscribeByPromocode'] == 1 and is not selected, active or inventoried,
                     we automatically subscribe it #390880 */
                    if (
                        !in_array($bonusType, ['active', 'inventory']) &&
                        !empty($query['PromoCode']) &&
                        !empty($bonusList) &&
                        count($bonusList) === 1 &&
                        isset($bonusList[0]['SubscribeByPromocode']) &&
                        $bonusList[0]['SubscribeByPromocode'] === '1' &&
                        $bonusList[0]['Active'] === 0 &&
                        $bonusList[0]['Selected'] === 0 &&
                        $bonusList[0]['Inventoried'] === 0
                    ) {
                        $query = [
                            'IDBonus' => $bonusList[0]['ID'],
                            'Status' => 1,
                            'PromoCode' => $query['PromoCode'],
                        ];

                        $selectedBonus = json_decode($ajax->Bonus($query), true);

                        if (
                            $selectedBonus &&
                            !empty($selectedBonus['Status']) &&
                            $selectedBonus['Status'] === 1
                        ) {
                            $bonusList[0]['Selected'] =
                                (!isset($selectedBonus['Bonus']) || isset($selectedBonus['Bonus']['isActivated'])) ? 1 : 0;

                            $bonusList[0]['Active'] = isset($selectedBonus['Bonus']['isActivated']) ? 1 : 0;
                            $bonusList[0]['Inventoried'] = isset($selectedBonus['Bonus']['isInventoried']) ? 1 : 0;
                        }
                    }

	                if (in_array($bonusType, ['active', 'inventory'])) {
	                    foreach ($bonusList as $bonusListItemId => $bonusListItem) {
	                    	if (($bonusListItem['Selected'] != '1' && $bonusType === 'active')
                                || ($bonusListItem['Inventoried'] != '1' && $bonusType === 'inventory')) {
	                            unset($bonusList[$bonusListItemId]);
	                        }
	                    }
	                    $bonusList = array_values($bonusList);
	                }

	                break;
	
	        }
        } catch (\Exception $ex) {
        	throw new ApiException($ex->getMessage(), $ex->getCode());
        }

        if (!is_array($bonusList)) {
            throw new ApiException(_('Bonus result is not list'), 400);
        }

        return $bonusList;
    }

    /**
     * Select, Take bonus
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|mixed|null|object}
     * @throws {ApiException}
     */
    public function put($request, $query, $params = [])
    {
        if (empty($request['id'])) {
            throw new ApiException(_('Bonus ID Not found'), 404);
        }

        if (empty($request['type'])) {
            $request['type'] = 'subscribe';
        }

        $A = new Ajax();
        $status = (int)($request['type'] == 'take' ? 2 : $request['type'] == 'subscribe');

        $result = $A->Bonus([
            'IDBonus' => $request['id'],
            'Status' => $status
        ]);

        $status = json_decode($result, 1);

        if (!is_array($status)) {
            throw new ApiException(dgettext('loyalty', $result), 400);
        }

        return $status;

    }

    /**
     * @SWG\Definition(
     *     definition="BonusSubscriptionResult",
     *     description="Bonus subscription result",
     *     type="object",
     *     @SWG\Property(
     *         property="ID",
     *         type="integer",
     *         description="Bonus id"
     *     ),
     *     @SWG\Property(
     *         property="Selected",
     *         type="integer",
     *         enum={0, 1},
     *         description="Bonus selected status"
     *     )
     * )
     *
     * @SWG\Post(
     *     path="/bonuses/{id}",
     *     description="Subscribes to bonus.",
     *     tags={"bonus"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="Bonus id",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="PromoCode",
     *                 type="string",
     *                 description="Bonus promo code",
     *             ),
     *             @SWG\Property(
     *                 property="Selected",
     *                 type="integer",
     *                 enum={0, 1},
     *                 description="Bonus selected status"
     *             )
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *             ref="#/definitions/BonusSubscriptionResult"
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
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {mixed}
     * @throws {ApiException}
     */
    public function post($request, $query, $params)
    {
        if (empty($params['id'])) {
            throw new ApiException(_('Bonus ID Not found'), 404);
        }

        if (!isset($request['Selected'])) {
            throw new ApiException(_('Bonus Selected Not found'), 404);
        }

        $A = new Ajax();
        $result = $A->Bonus([
            'IDBonus' => $params['id'],
            'Status' => (int)$request['Selected'],
            'PromoCode' => !empty($request['PromoCode']) ? $request['PromoCode'] : '',
        ]);
        $status = json_decode($result, 1);

        if (!is_array($status) || $status['Status'] != 1) {
            throw new ApiException(dgettext('loyalty', $result), 400);
        }

        return $request;
    }

    /**
     * @SWG\Delete(
     *     path="/bonuses/{id}",
     *     description="Cancel bonus",
     *     tags={"bonus"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="Bonus id",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 type="string",
     *                 property="balance",
     *                 description="Balance after cancelation",
     *                 example="23699.89"
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
     * Cancel bonus
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {mixed}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params)
    {
        if (empty($params['id'])) {
            throw new ApiException('Bonus ID Not found', 404);
        }

        $LBID = !empty($request['LBID']) ? $request['LBID'] : 0;

        $A = new Ajax();
        $result = $A->bonus_cancel(['IDBonus' => $params['id'], 'LBID' => $LBID]);
        $status = json_decode($result, 1);

        if (!is_array($status)) {
            throw new ApiException(dgettext('loyalty', $result), 400);
        } else {
            if (!empty($status['error'])) {
                throw new ApiException(dgettext('loyalty', $status['error']), 400);
            }
        }

        return $status;
    }
}
