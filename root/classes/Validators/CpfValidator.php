<?php

declare(strict_types=1);

namespace eGamings\WLC\Validators;

class CpfValidator extends AbstractValidator
{
    /**
     * @param string $value
     * @param bool $params
     * @param array $data
     * @param string $field
     *
     * @return bool
     */
    public function validate($value, $params, $data, $field): bool
    {
        if (!$params || empty($value)) {
            return true;
        }

        return $this->isCPF((string) $value) || $this->isCNPJ((string) $value);
    }

    /**
     * Checks if a value is CPF
     *
     * @param string $value
     * @return bool
     */
    private function isCPF (string $value): bool
    {

        if (!preg_match('/^(\d{11})$|^((\d{3}\.){2}\d{3}-\d{2})$/', $value)) {
            return false;
        }

        $value = preg_replace('/\D/', '', $value);

        if (preg_match("/^{$value[0]}{11}$/", $value)) {
            return false;
        }

        $dig = 0;
        for ($i = 0; $i < 9; $i++) {
            $dig += (int)$value[$i] * (10 - $i);
        }

        if ((int)$value[9] !== ((($dig %= 11) < 2) ? 0 : 11 - $dig)) {
            return false;
        }

        $dig = 0;
        for ($i = 0; $i < 10; $i++) {
            $dig += (int)$value[$i] * (11 - $i);
        }

        if ((int)$value[10] !== ((($dig %= 11) < 2) ? 0 : 11 - $dig)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a value is CNPJ
     *
     * @param string $value
     * @return bool
     */
    private function isCNPJ(string $value): bool
    {

        if (!preg_match('/^(\d{14})$|^(\d{2}(\.\d{3}){2}\/\d{4}-\d{2})$/', $value)) {
            return false;
        }

        $cnpj = preg_replace('/\D/', '', $value);

        if (preg_match("/^{$cnpj[0]}{14}$/", $cnpj)) {
            return false;
        }

        $dig = 0;
        for ($i = 0; $i < 12; $i++) {
            $dig += (int)$cnpj[$i] * (($i < 4 ? 5 : 13) - $i);
        }

        if ((int)$cnpj[12] != ((($dig %= 11) < 2) ? 0 : 11 - $dig)) {
            return false;
        }

        $dig = 0;
        for ($i = 0; $i < 13; $i++) {
            $dig += (int)$cnpj[$i] * (($i < 5 ? 6 : 14) - $i);
        }

        if ((int)$cnpj[13] != ((($dig %= 11) < 2) ? 0 : 11 - $dig)) {
            return false;
        }

        return true;
    }
}
