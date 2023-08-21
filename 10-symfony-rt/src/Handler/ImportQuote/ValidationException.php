<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

class ValidationException extends \Exception
{
    public function __construct(private array $errors, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
