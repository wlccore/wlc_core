<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="transactions",
 *     description="Transactions"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Transaction",
 *     description="Transaction",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="Transaction ID"
 *     ),
 *     @SWG\Property(
 *         property="System",
 *         type="string",
 *         description="Payment system",
 *         example="Wire Card"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="string",
 *         description="Transaction amount",
 *         example="5.00"
 *     ),
 *     @SWG\Property(
 *         property="AmountEur",
 *         type="string",
 *         description="Transaction amount in eur currency",
 *         example="12.05"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         description="Transaction status (
               -5 - payment system notify error,
               -50 - rejected,
               -55 - canceled by user,
               -60 - complete failed,
               95 - pending,
               99 - finalize failed,
               100 - complete,
               75 - put in payment queue,
               50 - confirmed,
                1 - updated,
                0 - new,
               110 - merchant complete
           )",
 *         enum={"-5", "-50", "-55", "-60", "95", "99", "100", "75", "50", "1", "0", "110"}
 *     ),
 *     @SWG\Property(
 *         property="Note",
 *         type="string",
 *         description="Transaction note"
 *     ),
 *     @SWG\Property(
 *         property="Date",
 *         type="string",
 *         description="Created date"
 *     ),
 *     @SWG\Property(
 *         property="DateIso",
 *         type="string",
 *         description="Created date in iso format"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         description="Transaction currency"
 *     ),
 *     @SWG\Property(
 *         property="Wallet",
 *         type="integer",
 *         description="Transaction user wallet (if exist)"
 *     )
 * )
 */

/**
 * @class TransactionResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class TransactionResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/transactions",
     *     description="Returns a list of user transactions",
     *     tags={"transactions"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         type="string",
     *         in="query",
     *         description="Filter by status"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Transactions list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Transaction"
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
     * Returns user`s transaction list
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params Route params
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
    	$data = [];
    	$user = new User();
    	
    	if (isset($request['status'])) {
    		$data['status'] = (int) $request['status']; 
    	}
    	
        $result = explode(',', $user->fetchTransactions($data), 2);

        if ($result[0] != 1) {
            throw new ApiException($result[1], 400);
        }

        $result = json_decode($result[1], true);

        foreach ($result as &$transaction) {
            $transaction['System'] = $this->mapMethod($transaction['System']);

            if (empty($transaction['Note'])) {
                continue;
            }

            switch (array_shift(explode(',', $transaction['Note']))) {
                case 'RejectCode1':
                    $transaction['Note'] = _('Insufficient monetary turnover') . strstr($transaction['Note'],'[');
                    break;
                case 'RejectCode2':
                    $transaction['Note'] = _('User\'s profile (personal data) wasn\'t filled') . strstr($transaction['Note'],'[');
                    break;
                case 'RejectCode3':
                    $transaction['Note'] = _('Need verification') . strstr($transaction['Note'],'[');
                    break;
                case 'RejectCode4':
                    $transaction['Note'] = _('Need additional verification') . strstr($transaction['Note'],'[');
                    break;
            }
        }

        return $result;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function mapMethod(string $method): string
    {
        return [
            'transfer' => _('Gift'),
        ][$method] ?? $method;
    }
}
