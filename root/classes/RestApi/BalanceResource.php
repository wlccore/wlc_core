<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Balance;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="balance",
 *     description="Balance"
 * )
 */

/**
 * @class BalancesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class BalanceResource extends AbstractResource
{
	
	/**
	 * @SWG\Get(
	 *     path="/balance",
	 *     description="Get balance",
	 *     tags={"balance"},
	 *     @SWG\Parameter(
	 *         name="systemId",
	 *         in="query",
	 *         required=true,
	 *         type="integer",
	 *         description="Merchant system identifier",
	 *         default=""
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="Successful operation",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="available",
	 *                  type="number"
	 *              ),
	 *              @SWG\Property(
	 *                  property="currency",
	 *                  type="string"
	 *              ),
	 *              @SWG\Property(
	 *                  property="loyaltyblock",
	 *                  type="number"
	 *              )
	 *          ),
	 *    ),
	 *     @SWG\Response(
	 *          response="400",
	 *          description="Error",
	 *          @SWG\Schema(
	 *              ref="#/definitions/ApiException"
	 *          )
	 *     )
	 * )
	 */
	
	/**
	 * @public
	 * @method get
	 * @param {array} $request
	 * @param {array} $query
	 * @param {array} [$params=[]]
	 * @return {array}
	 * @throws {ApiException}
	 */
	public function get($request, $query, $params = [])
	{
		if (!User::isAuthenticated() || empty($_SESSION['user'])) {
			throw new ApiException(_('User not authorized'), 401);
		}
		
		if (empty($request['systemId']) || !is_numeric($request['systemId'])) {
			throw new ApiException(_('Merchant system not selected or invalid'), 400);
		}
		
		try {
			$balance = new Balance();
			$res = $balance->get($_SESSION['user']['id'], $request['systemId']);
		} catch (\Exception $ex) {
			throw new ApiException($ex->getMessage(), 400, null, [], $ex->getCode());
		}
		
		if (is_array($res) && isset($res['error']) && isset($res['code'])) {
			throw new ApiException($res['error'], 400, null, [], $res['code']);
		}
		
		return $res;
	}
	
	/**
	 * @SWG\Post(
	 *     path="/balance",
	 *     description="Credit or withdraw balance",
	 *     tags={"balance"},
	 *     @SWG\Parameter(
	 *         name="systemId",
	 *         in="query",
	 *         required=true,
	 *         type="number",
	 *         description="Merchant system identifier",
	 *         default=""
	 *     ),
	 *     @SWG\Parameter(
	 *         name="amount",
	 *         in="query",
	 *         required=true,
	 *         type="number",
	 *         description="Amount. Positive to credit, negative to withdraw",
	 *         default=""
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="Returns transfer result",
	 *         @SWG\Schema(
	 *             @SWG\Property(
	 *                 property="result",
	 *                 type="boolean"
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
	 * @method post
	 * @param {array} $request
	 * @param {array} $query
	 * @param {array} [$params=[]]
	 * @return {array}
	 * @throws {ApiException}
	 */
	public function post($request, $query, $params = [])
	{
		if (!User::isAuthenticated() || empty($_SESSION['user'])) {
			throw new ApiException(_('User not authorized'), 401);
		}
		
		if (empty($request['systemId']) || !is_numeric($request['systemId'])) {
			throw new ApiException(_('Merchant system not selected or invalid'), 400);
		}
		
		if (empty($request['amount']) || !is_numeric($request['amount'])) {
			throw new ApiException(_('Amount is not selected or invalid'), 400);
		}
		
		try {
			$balance = new Balance();
			if ($request['amount'] > 0) {
				$res = $balance->credit($_SESSION['user']['id'], $request['systemId'], $request['amount'], $_SESSION['user']['currency']);
			} else {
				$request['amount'] = abs($request['amount']);
				$res = $balance->withdraw($_SESSION['user']['id'], $request['systemId'], $request['amount'], $_SESSION['user']['currency']);
			}
		} catch(\Exception $ex) {
			throw new ApiException($ex->getMessage(), 400, null, [], $ex->getCode());
		}
		
		if (is_array($res) && isset($res['error']) && isset($res['code'])) {
			throw new ApiException($res['error'], 400, null, [], $res['code']);
		}
		
		return ['result' => $res];
	}
	
}
