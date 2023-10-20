<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="bets",
 *     description="Game transactions"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="GameTransaction",
 *     description="Game transaction",
 *     type="object",
 *     @SWG\Property(
 *         property="Action",
 *         type="string",
 *         enum={"bet", "win", "refund", "rakeOrFee", "credit", "debit", "winloss"}
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="string",
 *         example="21.00"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         example="EUR"
 *     ),
 *     @SWG\Property(
 *         property="Date",
 *         type="string",
 *         example="12:36:31 14.06.2017"
 *     ),
 *     @SWG\Property(
 *         property="GameName",
 *         type="string",
 *         example="Merry Spinning"
 *     ),
 *     @SWG\Property(
 *         property="Merchant",
 *         type="string",
 *         example="BoomingGames"
 *     )
 * )
 */

/**
 * @class BetsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class BetsResource extends AbstractResource
{
    /**
     * Maximal report period in days
     *
     * @property REPORT_PERIOD_IN_DAYS
     * @type int
     * @final
     */
    const REPORT_PERIOD_IN_DAYS = 3*366;

    /**
     * Normal date format
     * @property NORMAL_DATETIME_FORMAT
     * @type string
     * @final
     */
    const NORMAL_DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * Posible incoming date formats
     * @property $incomingFormats
     * @type array
     * @static
     * @final
     */
    protected static $incomingFormats = [
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:s.\0\0\0\Z'
    ];

    /**
     * @SWG\Get(
     *     path="/bets",
     *     description="Returns bets/wins history, max period 365 days. Or returns open rounds",
     *     tags={"bets"},
     *     @SWG\Parameter(
     *         name="startDate",
     *         in="query",
     *         type="string",
     *         description="Start period",
     *         default="2017-01-01T01:00:00"
     *     ),
     *     @SWG\Parameter(
     *         name="endDate",
     *         in="query",
     *         type="string",
     *         description="End period",
     *         default="2017-03-17T13:00:00"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         type="string",
     *         description="Set type = open to get user open rounds instead bets/wins history.",
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/GameTransaction"
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
     * Method fetch bets/wins history
     *
     * @public
     * @method get
     * @param $request
     * @param $query
     * @param $params
     * @return mixed {mixed}
     * @throws ApiException
     */
    public function get($request, $query, $params)
    {
        $data = [];
        $user = new User();
        $data['openRounds'] = isset($request['type']) && $request['type'] === 'open';

        if (!$data['openRounds']) {
            $data['startDate'] = $this->normalizeDate($request['startDate']);
            $data['endDate'] = $this->normalizeDate($request['endDate']);

            if (!$data['startDate'] || !$data['endDate']) {
                throw new ApiException('Bad date format', 400);
            }

            if (!$this->validateReportPeriod($data['startDate'], $data['endDate'])) {
                throw new ApiException('Report interval is more than ' . self::REPORT_PERIOD_IN_DAYS . ' days', 400);
            }
        }

        $result = explode(',', $user->fetchBetsHistory($data), 2);

        if ($result[0] != 1) {
            throw new ApiException($result[1], 400);
        }

        return json_decode($result[1], true);
    }

    /**
     * Validate date by self::$incomingFormat
     * and normalize it to NORMAL_DATETIME_FORMAT
     *
     * @private
     * @method normalizeDate
     * @param {string} $date - Date for normalize
     * @return {boolean|string}
     */
    private function normalizeDate($date)
    {
        if(empty($date)){
            return false;
        }

        foreach(self::$incomingFormats as $format){
            $d = \DateTime::createFromFormat($format, $date);
            if($d && $d->format($format) == $date){
                return $d->format(self::NORMAL_DATETIME_FORMAT);
            }
        }

        return false;
    }

    /**
     * Method validate report period by self::REPORT_PERIOD_IN_DAYS
     *
     * @private
     * @method validateReportPeriod
     * @param {string} $startDate - start report date in self::NORMAL_DATETIME_FORMAT format
     * @param {string} $endDate - end report date in self::NORMAL_DATETIME_FORMAT format
     * @return {boolean}
     */
    private function validateReportPeriod($startDate, $endDate)
    {
        if (empty($startDate) || empty($endDate)) {
            return false;
        }

        $startReportDate = \DateTime::createFromFormat(self::NORMAL_DATETIME_FORMAT, $startDate);
        $endReportDate = \DateTime::createFromFormat(self::NORMAL_DATETIME_FORMAT, $endDate);
        $reportInterval = $endReportDate->diff($startReportDate);
        
        return $reportInterval->days <= self::REPORT_PERIOD_IN_DAYS;
    }

}