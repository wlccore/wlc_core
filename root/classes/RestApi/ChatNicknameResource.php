<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Chat\Chat;
use eGamings\WLC\Front;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="chat nickname",
 *     description="Chat Nickname"
 * ),
 * @SWG\Definition(
 *     definition="ChatNickname",
 *     description="Chat Nickname object",
 *     type="object",
 *     @SWG\Property(
 *         property="nickname",
 *         type="string",
 *         description="User nickname",
 *         example="Zverenaklonitel3000",
 *     ),
 * )
 */
class ChatNicknameResource extends AbstractResource
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
     *     path="/chat/user/data",
     *     description="Returns chat nickname for autorized user",
     *     tags={"chat nickname"},
     *     @SWG\Response(
     *         response="200",
     *         description="Chat Nick object",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ChatNickname"
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
     *
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        return $this->chatService->getUserNickname(Front::User());
    }

    /**
     * @SWG\Post(
     *     path="/chat/user/data",
     *     description="Create chat nickname for autorized user",
     *     tags={"chat nickname"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"nickname", "password"},
     *             @SWG\Property(
     *                 property="nickname",
     *                 type="string",
     *                 description="User nickname",
     *                 example="Zverenaklonitel3000",
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Chat Nick object",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ChatNickname"
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
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     * )
     *
     * @param array|null $request
     * @param array $query
     * @param array $params
     * @return bool|string|void
     * @throws ApiException
     */
    public function post(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        return $this->chatService->createUserNickname(Front::User(), $request['nickname']);
    }

    /**
     * @SWG\Put(
     *     path="/chat/user/data",
     *     description="Update chat nickname for autorized user",
     *     tags={"chat nickname"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"nickname", "password"},
     *             @SWG\Property(
     *                 property="nickname",
     *                 type="string",
     *                 description="User nickname",
     *                 example="Zverenaklonitel3000",
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Chat Nick object",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ChatNickname"
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
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     * )
     *
     * @param array|null $request
     * @param array $query
     * @param array $params
     * @return bool|string|void
     * @throws ApiException
     */
    public function put(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        return $this->chatService->updateUserNickname(Front::User(), $request['nickname']);
    }
}
