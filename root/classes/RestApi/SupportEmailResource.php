<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Email;

/**
 * @SWG\Tag(
 *     name="support",
 *     description="Support"
 * )
 */

/**
 * @class SupportEmailResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Email
 */
class SupportEmailResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/supportEmail",
     *     description="Send an email to support team",
     *     tags={"support"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"senderName", "subject", "message", "senderEmail"},
     *             @SWG\Property(
     *                 property="senderName",
     *                 type="string",
     *                 description="Sender name"
     *             ),
     *             @SWG\Property(
     *                 property="senderEmail",
     *                 type="string",
     *                 description="Sender email"
     *             ),
     *             @SWG\Property(
     *                 property="subject",
     *                 type="string",
     *                 description="Email subject"
     *             ),
     *             @SWG\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Email message"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of mail sending",
     *         @SWG\Schema(
     *             type="boolean"
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
     * Send an email to support team.
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
        $replyTo = array($request['senderEmail'] => $request['senderName']);

        $message  = !empty($request['message']) ? $request['message'] : '';
        $message .= str_repeat('<br/>', 3) . '<hr color="#000" size="1" /><b>' . _('Sent from web-form at') . ' ' . _cfg('websiteName') . ', Email of customer: '.$request['senderEmail'].', '.$request['senderName'].'</b>';

        $result = Email::send(_cfg('supportEmail'), $request['subject'], $message, array(), $replyTo);

        return [
            'result' => $result
        ];
    }
}
