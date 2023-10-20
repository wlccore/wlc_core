<?php

declare(strict_types=1);

namespace eGamings\WLC\ConfirmationCode\Contracts;

interface CodeStorage
{
    /**
     * @param object $user
     * @param string $code
     *
     * @return array
     */
    public function pop(object $user, string $code): array;

    /**
     * @param object $user
     * @param string $code
     * @param array $data
     *
     * @return void
     */
    public function push(object $user, string $code, array $data): void;
}
