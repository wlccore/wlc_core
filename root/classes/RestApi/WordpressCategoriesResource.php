<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
/**
 * @SWG\Tag(
 *     name="Wordpress categories",
 *     description="Wordpress categories"
 * )
 *
 * @SWG\Definition(
 *     definition="WordpressCategory",
 *     description="Wordpress category object",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", example="13"),
 *     @SWG\Property(property="count", type="integer", example="13"),
 *     @SWG\Property(property="name", type="string", example="Category name"),
 *     @SWG\Property(property="slug", type="string", example="Category slug"),
 *     @SWG\Property(property="parent", type="integer", example="12"),
 *     @SWG\Property(property="description", type="string", example="Category description"),
 *     @SWG\Property(property="acf", type="object"),
 * )
 */
class WordpressCategoriesResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/static/categories",
     *     description="Returns wordpress categories.",
     *     tags={"Wordpress categories"},
     *     @SWG\Parameter(name="page", in="query", type="integer", default=""),
     *     @SWG\Parameter(name="count", in="query", type="integer", default="100"),
     *     @SWG\Parameter(name="order", in="query", type="string", default=""),
     *     @SWG\Parameter(name="orderby", in="query", type="string", default="id"),
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Wordpress categories",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/WordpressCategory"
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
     *     path="/static/categories/{slug}",
     *     description="Returns wordpress category by slug.",
     *     tags={"Wordpress categories"},
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns wordpress category by slug",
     *         @SWG\Schema(
     *             ref="#/definitions/WordpressCategory"
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
            return $this->getCategories($query);
        } else {
            return $this->getCategoryBySlug($params['slug'], $query);
        }
    }

    /**
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getCategories($query): array
    {
        $fieldsInResult = ['id', 'count', 'name', 'slug', 'parent'];
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

        return WordpressHelper::getDataFromWordpress('categories', $arrayData, $query);
    }

    /**
     * @param $slug
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getCategoryBySlug($slug, $query): array
    {
        $fieldsInResult = ['id', 'count', 'name', 'slug', 'parent', 'description', 'acf'];
        $arrayData = [
            '_fields' => implode(',', $fieldsInResult),
            'slug' => $slug
        ];

        return WordpressHelper::getDataFromWordpress('categories', $arrayData, $query);
    }
}
