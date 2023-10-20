<?php

declare(strict_types=1);

namespace eGamings\WLC\ConfirmationCode;

use eGamings\WLC\ConfirmationCode\Contracts\CodeGenerator;

final class RandomNumberCodeGenerator implements CodeGenerator
{
    /**
     * @var int
     */
    private $symbols;

    /**
     * @param int $symbols
     */
    public function __construct(int $symbols)
    {
        $this->symbols = $symbols;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        if ($this->symbols <= 0) {
            return '';
        }

        return (string)random_int(
            10 ** ($this->symbols - 1),
            10 ** $this->symbols - 1,
        );
    }
}
