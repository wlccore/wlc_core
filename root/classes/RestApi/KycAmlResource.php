<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\KycAml;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="kyc-aml",
 *     description="KYC/AML"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="KycAml",
 *     description="KYC/AML object",
 *     type="object",
 *     @SWG\Property(
 *         property="url",
 *         type="string",
 *         description="Url",
 *         example="https://example.com"
 *     )
 * )
 */
class KycAmlResource extends AbstractResource
{
    /**
     * @var KycAml
     */
    private $service;

    private const DEFAULT_SERVICE = 'ShuftiPro';

    public function __construct(?KycAml $service = null)
    {
        $this->service = $service ?? new KycAml();
    }

    /**
     * @SWG\Get(
     *     path="/kyc-aml",
     *     description="Returns KYC url for user",
     *     tags={"kyc-aml"},
     *     @SWG\Parameter(
     *         name="Service",
     *         in="query",
     *         description="Service",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns KYC url",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/KycAml"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
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
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();

        return $this->service->getUrlForUser($user);
    }

    /**
     * @SWG\Post(
     *     path="/kyc-aml",
     *     description="Generate KYC url for user",
     *     tags={"kyc-aml"},
     *     @SWG\Parameter(
     *         name="Service",
     *         in="query",
     *         description="Service",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="ok"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
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
     * @throws ApiException
     */
    public function post(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();

        return $this->service->generateUrlForUser($user);
    }
}
