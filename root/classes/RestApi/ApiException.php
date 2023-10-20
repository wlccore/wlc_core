<?php

namespace eGamings\WLC\RestApi;

/**
 * @SWG\Definition(
 *     definition="ApiException",
 *     type="object",
 *     @SWG\Property(
 *         property="errors",
 *         type="array",
 *         @SWG\Items(
 *             type="string"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="code",
 *         type="integer"
 *     )
 * )
 */

/**
 * @class ApiException
 * @namespace eGamings\WLC\RestApi
 * @extends \Exception
 */
class ApiException extends \Exception
{
    /**
     * Error list
     * @property $errors
     * @type array
     * @protected
     */
    protected $errors = [];

    /**
     * Funcore error code
     * @property $errorCode
     * @type int|null
     * @protected
     */
    protected $errorCode = NULL;

    /**
     * Constructor of class
     *
     * @public
     * @constructor
     * @method __construct
     * @param {string} [$message='']
     * @param {int} [$code=0]
     * @param {\Exception} [$previous=null]
     * @param {array} [$errors=array()]
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $errors = [], $errorCode = NULL)
    {
        parent::__construct($message, $code, $previous);

        if ($message) {
            $this->appendError($message);
        }

        if (is_int($errorCode)) {
            $this->errorCode = $errorCode;
        }

        $this->errors = array_merge($this->errors, $errors);
    }

    /**
     * Returns list errors
     *
     * @public
     * @method getErrors
     * @return {array}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns funcore error code
     *
     * @public
     * @method getErrorCode
     * @return int|null
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * Add new error to list of errors
     *
     * @public
     * @method appendError
     * @param {string} $error
     */
    public function appendError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Set error of message
     *
     * @public
     * @method setErrorMessage
     * @param {int|string} $key
     * @param {string} $message
     */
    public function setErrorMessage($key, $message)
    {
        $this->errors[$key] = $message;
    }
}
