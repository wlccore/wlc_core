<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Social;

class SocialNetworksResource extends AbstractResource
{
    /**
     *  @SWG\Definition(
     *     definition="socialNetwork",
     *     description="Social network",
     *     type="object",
     *     @SWG\Property(property="id", type="string", example="vk"),
     *     @SWG\Property(property="name", type="string", example="Vkontakte"),
     * )
     */

    /**
     * @SWG\Get(
     *     path="/socialNetworks",
     *     description="Returns socialNetworks.",
     *     tags={"Social networks"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/socialNetwork"
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
     * @public
     * @method get
     * @param $request
     * @param $query
     * @param $params
     * @return mixed {array}
     * @throws ApiException
     */
    public function get($request, $query, $params)
    {
        return self::getSocialNetworks();
    }

    /**
     * @return array
     */
    public static function getSocialNetworks(): array
    {
        $socialNetworks = _cfg('social');
        $socialNetworks = !is_array($socialNetworks) ? [] : $socialNetworks;
        $socialNetworksInfo = Social::getNetworks();
        $siteSocialNetworks = [];
        foreach(array_keys($socialNetworks) as $socialNetworkId) {
            $siteSocialNetwork = ['id' => $socialNetworkId];
            if (!empty($socialNetworksInfo[$socialNetworkId])) {
                $siteSocialNetwork['name'] = $socialNetworksInfo[$socialNetworkId];
            }
            $siteSocialNetworks[] = $siteSocialNetwork;
        }

        return $siteSocialNetworks;
    }
}