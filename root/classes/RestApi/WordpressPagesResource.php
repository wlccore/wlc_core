<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
/**
 * @SWG\Tag(
 *     name="Wordpress pages",
 *     description="Wordpress pages"
 * )
 *
 * @SWG\Definition(
 *     definition="WordpressPage",
 *     description="Wordpress page object",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", example="13"),
 *     @SWG\Property(property="date_gmt", type="datetime"),
 *     @SWG\Property(property="modified_gmt", type="string"),
 *     @SWG\Property(property="slug", type="string"),
 *     @SWG\Property(property="parent", type="integer", example="12"),
 *     @SWG\Property(property="title.rendered", type="string"),
 *     @SWG\Property(property="excerpt.rendered", type="string"),
 *     @SWG\Property(property="content.rendered", type="string"),
 *     @SWG\Property(property="acf", type="object"),
 * )
 */
class WordpressPagesResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/static/pages",
     *     description="Returns wordpress pages.",
     *     tags={"Wordpress pages"},
     *     @SWG\Parameter(name="page", in="query", type="integer", default=""),
     *     @SWG\Parameter(name="count", in="query", type="integer", default="100"),
     *     @SWG\Parameter(name="order", in="query", type="string", default=""),
     *     @SWG\Parameter(name="orderby", in="query", type="string", default="id"),
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Wordpress pages",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/WordpressPage"
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
     *     path="/static/pages/{slug}",
     *     description="Returns wordpress page by slug.",
     *     tags={"Wordpress pages"},
     *     @SWG\Parameter(name="slug", in="path", type="string", default=""),
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns wordpress pages by slug",
     *         @SWG\Schema(
     *             ref="#/definitions/WordpressPage"
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
     *
     * @public
     * @method get
     * @param $request
     * @param $query
     * @param $params
     * @return array {array}
     * @throws ApiException
     */
    public function get($request, $query, $params): array
    {
        if (empty($params['slug'])) {
            return $this->getPages($query);
        }

        return $this->getPageBySlug($params['slug'], $query);
    }

    /**
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getPages($query): array
    {
        $fieldsInResult = ['id', 'date_gmt', 'modified_gmt', 'slug', 'parent', 'title.rendered', 'excerpt.rendered'];
        $arrayData = [
            '_fields' => implode(',', $fieldsInResult),
            'per_page' => $query['count'] ?? 100,
            'orderby' => $query['orderby'] ?? 'id',
        ];

        if (isset($query['page'])) {
            $arrayData['page'] = $query['page'];
        }

        if (isset($query['order'])) {
            $arrayData['order'] = $query['order'];
        }

        return WordpressHelper::getDataFromWordpress('pages', $arrayData, $query);
    }

    /**
     * @param $slug
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getPageBySlug($slug, $query): array
    {
        $fieldsInResult = ['id', 'date_gmt', 'modified_gmt', 'slug', 'parent', 'title.rendered', 'excerpt.rendered', 'content.rendered', 'acf'];
        $arrayData = [
            '_fields' => implode(',', $fieldsInResult),
            'slug' => $slug
        ];

        return WordpressHelper::getDataFromWordpress('pages', $arrayData, $query);
    }
}
