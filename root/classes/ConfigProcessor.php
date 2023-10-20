<?php
namespace eGamings\WLC;

/**
 * Validates the installed configuration according to the validation rules
 * 
 * @example ../../tests/classes/ConfigProcessorTest.php
 */
class ConfigProcessor
{
    protected $config = [];
    protected $configDefinition = [];

    // @TODO: Enum? Tuple struct?
    public static $VALIDATOR_OK = 0x0;
    public static $VALIDATOR_ERROR = 0x1;
    public static $VALIDATOR_TYPES_MISMATCH = 0x2;
    public static $VALIDATOR_ERROR_VALUE_NOT_SET = 0x4;
    public static $VALIDATOR_ERROR_DEFINITION_NOT_FOUND = 0x8;

    protected $errorMessage = '';
    protected $definition = [];

    protected static $state = 0x0;
    protected $errors = [];
    protected $validatedValues = [];

    public function __construct(array $config, array $configDefinition)
    {
        $this->config = $config;
        $this->configDefinition = $configDefinition;

        self::$state = self::$VALIDATOR_OK;
    }

    /**
     * Returns the result of configuration validation
     * 
     * @return int self::$VALIDATOR_OK || self::$VALIDATOR_ERROR
     */
    public static function getState(): int
    {
        return self::$state;
    }

    /**
     * Checks whether the validation is successful
     *
     * @return bool
     */
    public static function isValidateOk(): bool
    {
        return self::$state === self::$VALIDATOR_OK;
    }

    /**
     * Updates the configuration in this object 
     * 
     * @param array $config [configName => configValue, ...]
     * 
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Updates the configuration definition in this object
     *
     * @param array $configDefinition [configName => configDefinition, ...]
     *
     * @return void
     */
    public function setConfigDefinition(array $configDefinition): void
    {
        $this->configDefinition = $configDefinition;
    }

    /**
     * Returns all found validation errors 
     * 
     * @return array [configName => errorMessage, ..]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns all successfully validated values 
     * 
     * @return array [configName => configValue, ...]
     */
    public function getValidatedValues(): array
    {
        return $this->validatedValues;
    }

    /**
     * Runs configuration check according to the rules
     * 
     * @param array $fallbacks [configName => fallBack, ...]
     * 
     * @return self::$state
     */
    public function validateConfig(array $fallbacks = []): int
    {
        $this->flushBeforeValidation();
        foreach($this->configDefinition as $configName => $definition) {
            $this->getConfigValue($configName, isset($fallbacks[$configName]) ? $fallbacks[$configName] : null);
        }

        $this->flushAfterValidation();
        return self::$state;
    }

    /**
     * Checks the passed config value according to validation rules 
     * 
     * @param string $definitionName Config name, e. g. "site_ip"
     * @param mixed  $fallback       Fallback for this config. Can be null
     * @param string $errorMessage   Custom error message
     * 
     * @return int Validation state {@see $this->checkValidDefinition()}
     */
    public function getConfigValue(string $definitionName, $fallback = null, string $errorMessage = ''): int
    {
        $errorMessage = $this->getErrorMessage($definitionName, $errorMessage);

        if (!isset($this->config[$definitionName])) {
            $this->config[$definitionName] = $fallback;
        }

        $result = $this->checkValidDefinition($definitionName);

        if ($result & self::$VALIDATOR_ERROR) {
            if (self::$VALIDATOR_ERROR_DEFINITION_NOT_FOUND & ~$result) {
                $customErrorMessage = $this->getErrorMessage($definitionName, '', self::$VALIDATOR_ERROR ^ $result);

                if ($customErrorMessage) {
                    $errorMessage = $customErrorMessage;
                } else {
                    if ($this->errorMessage) {
                        $errorMessage = $this->errorMessage;
                    }
                }
            }

            self::$state = self::$VALIDATOR_ERROR;
            $this->errors[$definitionName] = ($errorMessage ?: '[error_message]') .
            (self::$VALIDATOR_ERROR_VALUE_NOT_SET & ~$result ? ", got '" . gettype($this->config[$definitionName]) . "'" : '');

            return $result;
        }

        $this->validatedValues[$definitionName] = $this->config[$definitionName];

        $this->flushAfterCheck();

        return self::$VALIDATOR_OK;
    }

    /**
     * Returns a relevant error message
     * 
     * @param string $definitionName        Config name, e. g. "site_ip"
     * @param string $primaryErrorMessage   Fallback error message
     * @param string $customErrorMessageKey Error message key from $definition['errors'][$key] if exists
     * 
     * @return string
     */
    public function getErrorMessage(string $definitionName, string $primaryErrorMessage, ?int $customErrorMessageKey = null): string
    {
        $definition = $this->configDefinition[$definitionName] ?? null;
        $errorKey = $customErrorMessageKey ?? 'errorMessage';

        return $definition
            && !empty($definition['errors'])
            && !empty($definition['errors'][$errorKey]) ? $definition['errors'][$errorKey] : $primaryErrorMessage;
    }

    /**
     * Validates the config according to the rules 
     * 
     * @param string $definitionName Config name, e. g. "site_ip"
     * 
     * @return int Binary mask of result
     */
    public function checkValidDefinition(string $definitionName): int
    {
        $result = self::$VALIDATOR_ERROR;

        if (!isset($this->config[$definitionName])) {
            return $result | self::$VALIDATOR_ERROR_VALUE_NOT_SET;
        }

        $var = $this->config[$definitionName];

        if (!isset($this->configDefinition[$definitionName])) {
            return $result | self::$VALIDATOR_ERROR_DEFINITION_NOT_FOUND;
        }

        $definition = $this->configDefinition[$definitionName];

        $this->definition = $definition;

        $varType = gettype($var);

        if ($varType !== $definition['type']) {
            return $result | self::$VALIDATOR_TYPES_MISMATCH;
        }

        if (!empty($definition['validator']) && is_callable($definition['validator'])) {
            $validatorResult = $definition['validator']($var);

            if ($validatorResult ^ self::$VALIDATOR_OK) {
                $this->errorMessage = $definition['validatorError'] ?: '';

                return $result | $validatorResult;
            }
        }

        return self::$VALIDATOR_OK;
    }

    /**
     * Resetting service fields after each verification iteration 
     * 
     * @return void
     */
    protected function flushAfterCheck(): void
    {
        $this->errorMessage = '';
        $this->definition = [];
    }

    /**
     * Resetting service fields before validation 
     * 
     * @return void
     */
    protected function flushBeforeValidation(): void
    {
        self::$state = self::$VALIDATOR_OK;
        $this->errors = [];
        $this->validatedValues = [];
    }

    /**
     * Resetting service fields after validation 
     * 
     * @return void
     */
    protected function flushAfterValidation(): void
    {
        $this->flushAfterCheck();
    }
}