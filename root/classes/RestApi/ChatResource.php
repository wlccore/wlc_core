<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\User;
use eGamings\WLC\Chat\Chat;

/**
 * @SWG\Tag(
 *     name="chat",
 *     description="Chat"
 * )
 */

/**
 * @class ChatResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\User
 * @uses eGamings\WLC\Chat\Chat
 */
class ChatResource extends AbstractResource
{
    /**
     * @var Chat
     */
    private $chatService;

    public function __construct($chatService = null)
    {
        $this->chatService = $chatService ?? new Chat();
    }

    /**
     * @SWG\Get(
     *     path="/chat/rooms",
     *     description="Returns all chat rooms",
     *     tags={"chat"},
     *     @SWG\Response(
     *         response="200",
     *         description="Chatroom list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Chat"
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
    public function get(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $action = (!empty($params['action'])) ? $params['action'] : 'default';
        $result = [];

        $action = explode('/', $query['route'])[3];

    	switch($action) {
            case 'rooms':
                $result = $this->chatService->getAllChatRooms($_SESSION['User']['ID']);
                break;
            default:
                $result = null;
                break;
        }

        return $result;
    }

    /**
    * @SWG\Post(
     *     path="/chat/user",
     *     description="Create user for chat",
     *     tags={"chat"},
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
    public function post(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $action = explode('/', $query['route'])[3];

        switch($action) {
            case 'user':
                $user = Front::User();
                $result = $this->chatService->authenticateUser($user);
                break;
            default:
                $result = null;
                break;
        }

        return $result;
    }
}
