<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
/**
 * @SWG\Tag(
 *     name="Wordpress posts",
 *     description="Wordpress posts"
 * )
 *
 * @SWG\Definition(
 *     definition="WordpressPost",
 *     description="Wordpress post object",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", example="13"),
 *     @SWG\Property(property="date_gmt", type="datetime"),
 *     @SWG\Property(property="modified_gmt", type="string"),
 *     @SWG\Property(property="slug", type="string"),
 *     @SWG\Property(property="title.rendered", type="string"),
 *     @SWG\Property(property="excerpt.rendered", type="string"),
 *     @SWG\Property(property="content.rendered", type="string"),
 *     @SWG\Property(property="categories", type="object"),
 *     @SWG\Property(property="tags", type="object"),
 *     @SWG\Property(property="acf", type="object"),
 * )
 */
class WordpressPostsResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/static/posts",
     *     description="Returns wordpress posts.",
     *     tags={"Wordpress posts"},
     *     @SWG\Parameter(name="page", in="query", type="integer", default=""),
     *     @SWG\Parameter(name="count", in="query", type="integer", default="100"),
     *     @SWG\Parameter(name="order", in="query", type="string", default=""),
     *     @SWG\Parameter(name="orderby", in="query", type="string", default="id"),
     *     @SWG\Parameter(name="cat", in="query", type="integer", default=""),
     *     @SWG\Parameter(name="tag", in="query", type="integer", default=""),
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Wordpress posts",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/WordpressPost"
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
     *     path="/static/posts/{slug}",
     *     description="Returns wordpress post by slug.",
     *     tags={"Wordpress posts"},
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns wordpress posts by slug",
     *         @SWG\Schema(
     *             ref="#/definitions/WordpressPost"
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
            return $this->getPosts($query);
        } else {
            return $this->getPostBySlug($params['slug'], $query);
        }
    }

    /**
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getPosts($query): array
    {
        $fieldsInResult = ['id', 'date_gmt', 'modified_gmt', 'slug', 'title.rendered', 'excerpt.rendered'];
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

        if (isset($query['cat'])) {
            $arrayData['categories'] = $query['cat'];
        }

        if (isset($query['tag'])) {
            $arrayData['tags'] = $query['tag'];
        }

        return WordpressHelper::getDataFromWordpress('posts', $arrayData, $query);
    }

    /**
     * @param $slug
     * @param $query
     * @return array
     * @throws ApiException
     */
    private function getPostBySlug($slug, $query): array
    {
        $fieldsInResult = ['id', 'date_gmt', 'modified_gmt', 'slug', 'title.rendered', 'excerpt.rendered', 'content.rendered', 'categories', 'tags', 'acf'];
        $arrayData = [
            '_fields' => implode(',', $fieldsInResult),
            'slug' => $slug
        ];

        return WordpressHelper::getDataFromWordpress('posts', $arrayData, $query);
    }
}
