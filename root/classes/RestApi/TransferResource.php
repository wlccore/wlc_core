<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\ConfirmationCode\Contracts\CodeGenerator;
use eGamings\WLC\ConfirmationCode\Contracts\CodeStorage;
use eGamings\WLC\ConfirmationCode\RandomNumberCodeGenerator;
use eGamings\WLC\ConfirmationCode\RedisCodeStorage;
use eGamings\WLC\Email;
use eGamings\WLC\Front;
use eGamings\WLC\Loyalty\LoyaltyBonusesResource;
use eGamings\WLC\Sms;
use eGamings\WLC\TransferService;
use eGamings\WLC\User;
use Exception;
use RuntimeException;

/**
 * @SWG\Tag(
 *     name="transfer",
 *     description="Player-to-player transfers"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TransferConfig",
 *     description="TransferConfig object",
 *     type="object",
 *     @SWG\Property(
 *         property="MaxOnce",
 *         type="float",
 *         description="Maximum transfer amount per transaction",
 *         example="123.56"
 *     ),
 *     @SWG\Property(
 *         property="MaxDaily",
 *         type="float",
 *         description="Maximum transfer amount per day",
 *         example="123.56"
 *     ),
 *     @SWG\Property(
 *         property="CurrentDaily",
 *         type="float",
 *         description="Transfers amount for today",
 *         example="123.56"
 *     ),
 * )
 */
class TransferResource extends AbstractResource
{
    private const CONFIRMATION_CODE_TTL = 60 * 10;
    private const CONFIRMATION_CODE_KEY_PREFIX = 'transfer_confirmation_code';
    private const CONFIRMATION_CODE_SYMBOLS = 5;

    private const ID_BONUS = 'IDBonus';

    /**
     * @var TransferService
     */
    private $transferService;

    /**
     * @var CodeGenerator
     */
    private $codeGenerator;

    /**
     * @var CodeStorage
     */
    private $codeStorage;

    public function __construct(
        ?TransferService $service = null,
        ?CodeGenerator $codeGenerator = null,
        ?CodeStorage $codeStorage = null
    ) {
        $this->transferService = $service ?? new TransferService();

        $this->codeGenerator = $codeGenerator ?? new RandomNumberCodeGenerator(
            self::CONFIRMATION_CODE_SYMBOLS,
        );

        $this->codeStorage = $codeStorage ?? new RedisCodeStorage(
            self::CONFIRMATION_CODE_KEY_PREFIX,
            self::CONFIRMATION_CODE_TTL,
        );
    }

    /**
     * @SWG\Get(
     *     path="/transfer",
     *     description="Returns transfer config",
     *     tags={"transfer"},
     *     @SWG\Response(
     *         response="200",
     *         description="TransferConfig",
     *         @SWG\Schema(
     *             ref="#/definitions/TransferConfig"
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
     * @SWG\GET(
     *     path="/transfer/bonusInfo",
     *     description="Returns information about the bonus that will become available to the recipient of the transfer.",
     *     tags={"transfer"},
     *     @SWG\Response(
     *         response="200",
     *         description="TransferConfig",
     *         @SWG\Schema(
     *             ref="#/definitions/BonusDetails"
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
     *         response="404",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     *)
     */

    /**
     * @param array|null $request
     * @param array $query
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $config = $this->transferService->getConfig(Front::User());
        if (empty($config)) {
            return [];
        }

        $action = $params['action'] ? : '';
        switch ($action) {
            case 'bonusInfo':
                if ($config[self::ID_BONUS] <= 0) {
                    throw new ApiException(_('No bonus'), 404);
                }
                try {
                    return LoyaltyBonusesResource::BonusGet(['id' => $config[self::ID_BONUS]]);
                } catch (Exception $ex) {
                    $message = $ex->getMessage();
                    throw new ApiException(
                        $message === 'No bonus'
                            ? _('Bonus not found')
                            : (_('Bonus not found') . ': ' . $message)
                        , 404
                    );
                }
            default:
                return $config;
        }
    }

    /**
     * @SWG\Post(
     *     path="/transfer",
     *     description="Send code to confirm transfer",
     *     tags={"transfer"},
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
     *
     * @throws ApiException
     */
    public function post(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();

        $code = $this->codeGenerator->generate();
        $this->saveConfirmationCode($user, $code, $this->validateData($user, $request ?: []));

        try{
            $request['type'] === 'sms'
                ? $this->sendSms($user, $code)
                : $this->sendEmail($user, $code);
        } catch (Exception $ex) {
             throw new ApiException(_('Failed to send confirmation code'), 400);
        }
        
        return ['result' => sprintf(
            _('Message sent, confirmation code will be available for %d seconds'),
            self::CONFIRMATION_CODE_TTL,
        )];
    }

    /**
     * @SWG\Put(
     *     path="/transfer",
     *     description="Check code and make transfer",
     *     tags={"transfer"},
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
     *
     * @throws ApiException
     */
    public function put(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();

        if (!$code = (string)($request['code'] ?? '')) {
            throw new ApiException(_('Validation code is empty'), 400);
        }

        try {
            $data = $this->codeStorage->pop($user, $code);
        } catch (RuntimeException $exception) {
            throw new ApiException('code_not_found', 400);
        }

        $this->transferService->run($user, $data);

        return ['result' => _('Gift sent')];
    }

    /**
     * @param object $user
     * @param array $data
     *
     * @return void
     * @throws ApiException
     */
    private function validateData(object $user, array $data): array
    {
        if (!($type = $data['type'] ?? '') || !in_array($type, ['sms', 'email'], true)) {
            throw new ApiException('', 400, null, ['type' => _('Empty or wrong type field')]);
        }

        if (!$amount = (float)($data['amount'] ?? 0)) {
            throw new ApiException('', 400, null, ['amount' => _('Empty amount field')]);
        }

        if (!$email = $data['email'] ?? null) {
            throw new ApiException('', 400, null, ['email' => _('Email field is empty')]);
        }

        if ($type === 'sms' && (!$user->phone1 || !$user->phone2)) {
            throw new ApiException(_('Empty phone number'), 400);
        }

        if ($type === 'sms' && ($user->phone_verified <= 0)) {
            throw new ApiException(_('To send a gift kindly confirm your phone number'), 400);
        }

        if ($type === 'email' && !$user->email) {
            throw new ApiException(_('Empty email'), 400);
        }

        if ($type === 'email' && $user->email_verified <= 0) {
            throw new ApiException(_('To send a gift kindly confirm your e-mail'), 400);
        }

        if (!$recipient = (new User())->getUserByEmail($email)) {
            throw new ApiException('', 400, null, ['email' => _('Please, check the field "Recipient e-mail"')]);
        }

        if ($recipient->id === $user->id) {
            throw new ApiException(_('You cannot send a gift to yourself'), 400);
        }

        if ($recipient->email_verified <= 0) {
            throw new ApiException('', 400, null, ['email' => _('To send a gift, the recipient\'s e-mail must be confirmed')]);
        }

        return ['RecipientLogin' => $recipient->id, 'Amount' => $amount];
    }

    /**
     * @param object $user
     * @param string $code
     *
     * @return void
     * @throws ApiException
     */
    private function sendSms(object $user, string $code): void
    {
        $message = _('Transfer confirmation code: ') . $code;

        try {
            Sms::send($user->phone2, $user->phone1, $message);
        } catch (RuntimeException $exception) {
            throw new ApiException(_($exception->getMessage()), 400, $exception);
        }
    }

    /**
     * @param object $user
     * @param string $code
     *
     * @return void
     * @throws ApiException
     */
    private function sendEmail(object $user, string $code): void
    {
        if (!$this->transferService->sendConfirmationEmail($user, $code)) {
            $subject = 'Confirmation code';
            $message = _('Transfer confirmation code: ') . $code;

            Email::send($user->email, $subject, $message);
        }
    }

    /**
     * @param object $user
     * @param string $code
     * @param array $data
     *
     * @return void
     * @throws ApiException
     */
    private function saveConfirmationCode(object $user, string $code, array $data): void
    {
        try {
            $this->codeStorage->push($user, $code, $data);
        } catch (RuntimeException $exception) {
            throw new ApiException(_($exception->getMessage()), 400, $exception);
        }
    }
}
