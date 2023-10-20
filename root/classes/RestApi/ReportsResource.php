<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\System;
use Exception;

/**
 * @SWG\Tag(
 *     name="Reports",
 *     description="Reports"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ReportsObject",
 *     description="Report"
 * )
 */
class ReportsResource extends AbstractResource
{
    private const URL = 'Reports';
    private const REPORTS_URL = [
        'Reference/GetReports',
        'v2/Reference/GetReports',
    ];
    private const REPORTS_TTL = 600;

    /**
     * @SWG\Get(
     *     path="/reports",
     *     description="Return report",
     *     @SWG\Parameter(
     *         name="report",
     *         type="string",
     *         in="query",
     *         description="Report"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Report",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ReportsObject"
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
     * Returns report response
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return bool|string|null
     * @throws ApiException
     * @throws Exception
     */
    public function get($request, $query, $params = [])
    {
        $report = !empty($query['report']) ? $query['report'] : '';
        if (empty($report) || !in_array($report, self::getReports(), true)) {
            throw new ApiException('Report not found', 404);
        }

        $response = self::getReport($query);
        $errors = self::getErrors((string) $response);

        if (!empty($errors)) {
            throw new ApiException($errors, 400);
        }

        return $response;
    }

    /**
     * @return mixed|null
     */
    private static function getReports()
    {
        return Cache::result(
            'wlc_api_reports',
            static function () {
                return array_merge(...array_map(
                    static function ($v) {
                        return json_decode(self::getReport(['report' => $v]), true);
                    },
                    self::REPORTS_URL
                ));
            },
            self::REPORTS_TTL
        );
    }

    /**
     * @param array $params
     * @return bool|string|null
     * @throws Exception
     */
    private static function getReport(array $params)
    {
        $system = System::getInstance();
        $url = '/' . self::URL;
        $transactionId = $system->getApiTID($url);
        $hash = md5(self::URL . '/0.0.0.0/' . $transactionId . '/' .
            _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $data = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'lang' => _cfg('language')
        ];
        $data = array_merge($data, $params);
        $url .= '?&' . http_build_query($data);

        return $system->runFundistAPI($url);
    }

    /**
     * @param string $response
     * @return string
     */
    private static function getErrors(string $response): string
    {
        $response = json_decode($response, true);
        if (!empty($response['errors'])) {
            return json_encode($response['errors']);
        }

        return '';
    }
}
