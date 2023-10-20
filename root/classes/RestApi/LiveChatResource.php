<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\LiveChat;

/**
 * @SWG\Tag(
 *     name="livechat",
 *     description="Live chat"
 * )
 */

/**
 * @class LiveChatResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\LiveChat
 */
class LiveChatResource extends AbstractResource
{
    /**
     * Current live chat provider
     *
     * @property $LiveChatProvider
     * @type object
     * @private
     */
    private $LiveChatProvider;

    /**
     * Constructor of class
     *
     * @public
     * @constructor
     * @method __construct
     * @throws {ApiException}
     */
    public function __construct()
    {
        $this->LiveChatProvider = LiveChat::getInstance();

        if (!$this->LiveChatProvider) {
            throw new ApiException(_('Not supported exception'), 400);
        }
    }

    /**
     * @SWG\Get(
     *     path="/liveChat",
     *     description="Get data of api chat",
     *     tags={"livechat"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="dataType",
     *         in="query",
     *         description="Data type",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="recordId",
     *         in="query",
     *         description="Record id",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns data if api chat",
     *         @SWG\Schema(
     *             type="object"
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
     * Get data of api chat
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params = [])
    {
        if (empty($query['dataType']))
            throw new ApiException(_('Invalid request parameters'), 400);

        $requestParams = [
            'dataType' => $query['dataType'],
            'recordId' => !empty($query['recordId']) ? $query['recordId'] : ''
        ];

        return $this->LiveChatProvider->getDataApi($requestParams);
    }

    /**
     * @SWG\Post(
     *     path="/liveChat",
     *     description="Save user conversation",
     *     tags={"livechat"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="getMessages",
     *         in="query",
     *         description="Return messages",
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="recordId",
     *         in="query",
     *         description="Record id",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="event_name",
     *         in="query",
     *         description="Event name",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="string",
     *                 example="ok"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Method is not supported",
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
     * Save user conversation
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        if (!empty($request['getMessages']) && !empty($request['recordId']))
            $request = array_merge($request, $this->LiveChatProvider->getDataApi(['dataType' => 'conversationMessages', 'recordId' => $request['recordId']]));

        if (isset($request['event_name']) && !empty($request['chat']['messages'])) {
            switch ($request['event_name']) {
                case 'chat_finished':

                    $contact = $this->LiveChatProvider->refactResponseConversations($request);
                    $this->LiveChatProvider->AddContact($contact);

                    return ['result' => 'ok'];
                    break;
                case 'chat_accepted':
                    //TODO: chat accept action
                    break;
                default:
                    throw new ApiException(_('Method is not supported'), 404);
                    break;
            }
        }
    }

}
