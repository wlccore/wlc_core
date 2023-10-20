<?php

declare(strict_types=1);

namespace eGamings\WLC\ConfirmationCode\Contracts;

interface CodeGenerator
{
    /**
     * @return string
     */
    public function generate(): string;
}
