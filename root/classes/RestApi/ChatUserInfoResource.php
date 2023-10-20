<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Chat\Chat;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="chat",
 *     description="Chat"
 * )
 */

class ChatUserInfoResource extends AbstractResource{

    private $chatService;

    public function __construct($chatService = null)
    {
        $this->chatService = $chatService ?? new Chat();
    }
    /**
     * @SWG\Post(
     *     path="/chat/userinfo",
     *     description="Getting information about a user",
     *     tags={"chat"},
     *     @SWG\Parameter(
     *         name="Room",
     *         type="string",
     *         required=true,
     *         in="path",
     *         description="The room the user is in"
     *     ),
     *     @SWG\Parameter(
     *         name="OccupantID",
     *         type="string|array",
     *         required=true,
     *         in="path",
     *         description="OccupantID"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="code",
     *                 type="int",
     *                 example="200",
     *                 description="Code"
     *             ),
     *             @SWG\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success",
     *                 description="Success status"
     *             ),
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Data list",
     *                 example={{"Login": "325_17", "JID": "325_17@qa-prosody.egamings.com", "Role": "participant", "Affiliation":"owner"}},
     *                 @SWG\Items(
     *                     type="object"
     *                 )
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
        $action = !empty($params['action']) ? $params['action'] : (!empty($query['action']) ? $query['action'] : null);

        if (empty($request['OccupantID']) && empty($request['Role'])) {
            throw new ApiException(_('OccupantID or Role required'), 402);
        }
        if (empty($request['Room'])) {
            throw new ApiException(_('Room cannot be empty'), 402);
        }

        if (is_array($request['OccupantID'])) {
            $OccupantID = implode(',', $request['OccupantID']);
        } else {
            $OccupantID = $request['OccupantID'] ?? '';
        }

        return $this->chatService->getUserInfo($request['Room'], $OccupantID, $request['Role']);
    }
}

