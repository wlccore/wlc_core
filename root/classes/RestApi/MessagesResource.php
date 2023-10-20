<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\Messages as MessageService;

/**
 * @SWG\Tag(
 *     name="messages",
 *     description="Messages"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Message",
 *     description="Message to the user",
 *     type="object",
 *     @SWG\Property(
 *         property="Content",
 *         type="string",
 *         description="Message text (json)"
 *     ),
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Message ID",
 *         example="147_123321"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Message title (json)"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         description="Message status",
 *         enum={"new", "readed"}
 *     )
 * )
 */

/**
 * @class MessagesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\Messages
 */
class MessagesResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/messages",
     *     description="Returns user messages",
     *     tags={"messages"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns user messages",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Message"
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
     * Returns current user messages
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} $params
     * @return {array}
     * @throws {\Exception}
     */
    public function get($request, $query, $params)
    {
        $action = !empty($params['action']) ? $params['action'] : '';
        $messagesService = new MessageService();

        switch ($action) {
            case 'open_mail_message':
                $messageParams = !empty($params['id']) ? $params['id'] : '';

                if (!$messagesService->isCorrectMailParams($messageParams)) {
                    throw new ApiException('Wrong request params' . json_encode($params), 400);
                }

                list($list_id, $user_id, $export_list_id) = explode('_', $messageParams);

                $user = Front::User('getUserById', [$user_id]);

                if (empty($user)) {
                    throw new ApiException(_('User not found'), 401);
                }

                $result = $messagesService->updateMessageStatus($user_id, $list_id . '_' . $export_list_id, 1);
                header('Content-type: image/png');
                echo(base64_decode($result));
                exit();
                break;
        }

        $user_id = (int)Front::User('id');
        if (!$user_id) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $userMessages = $messagesService->getMessages($user_id);

        return json_decode($userMessages, true);
    }

    /**
     * @SWG\Post(
     *     path="/messages/{id}",
     *     description="Set message status readed",
     *     tags={"messages"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         required=true,
     *         type="string",
     *         in="path",
     *         description="Message id"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="string"
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
     * Set message status readed
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params)
    {
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $list_id = $this->getId($params);

        if (is_null($list_id)) {
            throw new ApiException('Bad request'.json_encode($request), 400);
        }

        $messagesService = new MessageService();

        $result = $messagesService->updateMessageStatus($user_id, $list_id, 1);

        return $result;
    }

    /**
     * @SWG\Delete(
     *     path="/messages/{id}",
     *     description="Set message status deleted",
     *     tags={"messages"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         required=true,
     *         type="string",
     *         in="path",
     *         description="Message id"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="string"
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
     * Set message status deleted
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params)
    {
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $list_id = $this->getId($params);

        if (is_null($list_id)) {
            throw new ApiException(_('Bad request'), 400);
        }

        $messagesService = new MessageService();

        $result = $messagesService->updateMessageStatus($user_id, $list_id, -100);

        return $result;

    }

    /**
     * Returns id from parameters
     *
     * @private
     * @method getId
     * @param {array} $params
     * @return {int|null}
     */
    private function getId($params)
    {
        return isset($params['id']) ? $params['id'] : null;
    }


}
