<?php

namespace eGamings\WLC\RestApi;

use DateTime;

class ApiExceptionWithBlockUntil extends \Exception
{
    protected $errors = [];

    protected $blockUntil = 0;

    public function __construct($message = '', $code = 0, \Exception $previous = null, $errors = [], $blockUntil = 0)
    {
        parent::__construct($message, $code, $previous);

        if ($message) {
            $this->appendError($message);
        }

        $this->errors = array_merge($this->errors, $errors);

        $this->blockUntil = $blockUntil;
    }

    public function getErrors()
    {
        return $this->errors;
    }


    public function appendError($error)
    {
        $this->errors[] = $error;
    }

    public function setErrorMessage($key, $message)
    {
        $this->errors[$key] = $message;
    }

    public function setBlockUntil($blockUntil) 
    {
        $this->blockUntil = $blockUntil;
    }

    public function getBlockUntil()
    {
        return $this->blockUntil;
    }
}
