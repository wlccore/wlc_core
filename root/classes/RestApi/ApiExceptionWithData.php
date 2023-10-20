<?php

namespace eGamings\WLC\RestApi;

class ApiExceptionWithData extends \Exception
{
    protected $data = [];

    public function __construct(string $message, int $code = 0, \Exception $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);

        if ($message) {
            $this->appendData($message);
        }

        $this->data = array_merge($this->data, $errors);
    }

    public function getData(): array
    {
        return $this->data;
    }


    public function appendData(string $message): void
    {
        $this->data[] = $message;
    }

    public function setDataMessage(string $key, string $message)
    {
        $this->data[$key] = $message;
    }

}

