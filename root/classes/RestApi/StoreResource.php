<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Loyalty\LoyaltyStoreResource;

/**
 * @SWG\Tag(
 *     name="store",
 *     description="Store"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="StoreItem",
 *     description="Store item",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Order",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Quantity",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Price",
 *         type="object",
 *         example={"LOYALTY": "30000", "EUR": "200"}
 *     ),
 *     @SWG\Property(
 *         property="AddDate",
 *         type="string",
 *         description="Date item in the store"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Item status (1 - available, 0 - unavailable)"
 *     ),
 *     @SWG\Property(
 *         property="Categories",
 *         type="array",
 *         description="Item category",
 *         @SWG\Items(
 *             type="string"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="Image",
 *         type="string",
 *         description="Item image"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="StoreCategory",
 *     description="Store category",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Order",
 *         type="string"
 *     ),
 *     @SWG\Property(
 *         property="Items",
 *         type="string",
 *         description="Count of items in the category"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Category status (1 - available, 0 - unavailable)"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="StoreOrder",
 *     description="Store order",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Order id"
 *     ),
 *     @SWG\Property(
 *         property="IDUser",
 *         type="string",
 *         description="User id"
 *     ),
 *     @SWG\Property(
 *         property="IDItem",
 *         type="string",
 *         description="Order item id"
 *     ),
 *     @SWG\Property(
 *         property="IDTransaction",
 *         type="string",
 *         description="Transaction id"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"1", "99", "100"},
 *         description="Order status (1 - ordered, 99 - credited, 100 - closed)"
 *     ),
 *     @SWG\Property(
 *         property="AddDate",
 *         type="string",
 *         description="Order add date"
 *     ),
 *     @SWG\Property(
 *         property="Updated",
 *         type="string",
 *         description="Order update date"
 *     ),
 *     @SWG\Property(
 *         property="Note",
 *         type="string",
 *         description="Order note"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="string",
 *         description="Order price"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="object",
 *         description="Item name"
 *     ),
 * )
 */

/**
 * @class StoreResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Loyalty\LoyaltyStoreResource
 */
class StoreResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/store",
     *     description="Returns a list items of store",
     *     tags={"store"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of the store items",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="Items",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/StoreItem"
     *                 )
     *             ),
     *             @SWG\Property(
     *                 property="Categories",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/StoreCategory"
     *                 )
     *             ),
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
     *     path="/store/{id}",
     *     description="Returns item by id",
     *     tags={"store"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true,
     *         description="Id of the store item"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Store item",
     *         @SWG\Schema(
     *             ref="#/definitions/StoreItem"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Item not found",
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
     * @SWG\Get(
     *     path="/store/orders",
     *     description="Returns user orders",
     *     tags={"store"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Store item",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/StoreOrder"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Item not found",
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
     * Returns a list items of store
	 *
	 * @public
	 * @method get
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
    	$result = [];
    	$type = !empty($params['type']) ? $params['type'] : (!empty($request['type']) ? $request['type'] : '');

    	switch($type) {
    		case 'orders':
    			$result = LoyaltyStoreResource::StoreOrders();
    			break;

            case 'categories':
                $result = LoyaltyStoreResource::StoreGetCategories();
                break;
    			
    		default:
    			$result = LoyaltyStoreResource::StoreItems();
    			if (!empty($params['id']) && !empty($result['Items'])) {
    				$resultItems = $result['Items'];
    				$result = null;
    				$itemId = $params['id'];
    				
    				foreach($resultItems as $resultItem) {
    					if ($resultItem['ID'] == $itemId) {
    						$result = $resultItem;
    						break;
    					}
    				}

    				if (!$result) {
    					throw new ApiException(_('Item not found'), 404);
    				}
    			}
    			break;
    	}
    	return $result;
    }

    /**
     * @SWG\Put(
     *     path="/store/{id}",
     *     description="Purchase of goods",
     *     tags={"store"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="path",
     *         required=true,
     *         description="Id of the store item"
     *     ),
     *     @SWG\Parameter(
     *         name="quantity",
     *         type="integer",
     *         in="query",
     *         required=false,
     *         description="Quantity of the store item to buy"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of the store items",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="IDUser",
     *                 type="string",
     *                 description="User id"
     *             ),
     *             @SWG\Property(
     *                 property="IDItem",
     *                 type="string",
     *                 description="Item id"
     *             ),
     *             @SWG\Property(
     *                 property="ItemName",
     *                 type="object",
     *                 description="Item name"
     *             ),
     *             @SWG\Property(
     *                 property="IDTransaction",
     *                 type="integer",
     *                 description="Transaction id"
     *             ),
     *             @SWG\Property(
     *                 property="BalanceLeft",
     *                 type="number",
     *                 description="Users loyalty points balance after purchase"
     *             ),
     *             @SWG\Property(
     *                 property="ID",
     *                 type="integer"
     *             ),
     *             @SWG\Property(
     *                 property="ItemsLeft",
     *                 type="integer",
     *                 description="Number of items after purchase"
     *             ),
     *             @SWG\Property(
     *                 property="Credit",
     *                 type="integer",
     *                 description="If store item was programmed as balance voucher this fields provides amount to be credited to users balance"
     *             ),
     *             @SWG\Property(
     *                 property="Balance",
     *                 type="integer",
     *                 description="User balance"
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
     * Purchase of goods
	 *
	 * @public
	 * @method put
     * @param {array} $request
     * @param {array} $query
     * @return {array|string}
     * @throws {ApiException}
     */
    public function put($request, $query, $params)
    {
    	$storeItemId = !empty($params['id']) ? $params['id'] : (!empty($request['id']) ? $request['id'] : null);
        $orderQuantity = !empty($request['quantity']) ? $request['quantity'] : 1;

    	if (!$storeItemId) {
    		throw new ApiException('Error: id not found', 404);
    	}

    	$result = LoyaltyStoreResource::StoreBuy($storeItemId, $orderQuantity);
    	if (!empty($result['error'])) {
    		throw new ApiException($result['error'], 400);
    	}
    	return $result;
    }

}
