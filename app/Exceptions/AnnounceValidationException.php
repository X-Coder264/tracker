<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class AnnounceValidationException extends Exception
{
    private array $validationMessages;

    public function __construct(string $message = '', array $validationMessages = [], int $code = 0, Throwable $previous = null)
    {
        $this->validationMessages = $validationMessages;

        parent::__construct($message, $code, $previous);
    }

    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }
}
