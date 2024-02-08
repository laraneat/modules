<?php

namespace Laraneat\Modules\Traits;

use Symfony\Component\Console\Exception\InvalidOptionException;

/**
 * @mixin \Illuminate\Console\Command
 */
trait ConsoleHelpersTrait
{
    /**
     * Get trimmed argument
     *
     * @param string $key
     *
     * @return string
     */
    protected function getTrimmedArgument(string $key): string
    {
        return trim($this->argument($key));
    }

    /**
     * Get trimmed option
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function getTrimmedOption(string $key): string|null
    {
        $option = $this->option($key);
        return $option ? trim($option) : $option;
    }

    /**
     * Checks if the option is set (via CLI), otherwise asks the user for a value
     *
     * @param string $optionName
     * @param string $question
     * @param mixed $default
     * @param bool $required
     *
     * @return string
     */
    protected function getOptionOrAsk(string $optionName, string $question, mixed $default = null, bool $required = false): string
    {
        $value = $this->getTrimmedOption($optionName);

        if ($value === '' || $value === null) {
            $value = trim($this->ask($question, $default));
        }

        if ($required && empty($value)) {
            throw new InvalidOptionException(
                sprintf('The "%s" option is required', $optionName)
            );
        }

        return $value;
    }

    /**
     * Checks if the option is set (via CLI), otherwise proposes choices to the user
     *
     * @param string $optionName
     * @param string $question
     * @param array $choices
     * @param mixed $default
     *
     * @return string
     */
    protected function getOptionOrChoice(string $optionName, string $question, array $choices, mixed $default = null): string
    {
        $value = $this->getTrimmedOption($optionName);

        if ($value === '' || $value === null) {
            $value = trim($this->choice($question, $choices, $default));
        } elseif (!in_array(mb_strtolower($value), $choices, true)) {
            throw new InvalidOptionException(
                sprintf(
                    'Wrong "%s" option value provided. Value should be one of "%s".',
                    $optionName,
                    implode('" or "', $choices)
                )
            );
        }

        return $value;
    }

    /**
     * Get an option that is one of the valid values
     *
     * @param string $optionName
     * @param array $validValues
     *
     * @return string
     */
    protected function getOptionOneOf(string $optionName, array $validValues): string
    {
        $value = $this->getTrimmedOption($optionName);

        if (!in_array(mb_strtolower($value), $validValues, true)) {
            throw new InvalidOptionException(
                sprintf(
                    'Wrong "%s" option value provided. Value should be one of "%s".',
                    $optionName,
                    implode('" or "', $validValues)
                )
            );
        }

        return $value;
    }

    /**
     * Checks if the option is set (via CLI), otherwise, asks the user for confirmation
     *
     * @param string $optionName
     * @param string $question
     * @param bool $default
     *
     * @return bool
     */
    protected function getOptionOrConfirm(string $optionName, string $question, $default = false): bool
    {
        $value = $this->option($optionName);

        if ($value === null) {
            $value = $this->confirm($question, $default);
        }

        return (bool) $value;
    }
}
