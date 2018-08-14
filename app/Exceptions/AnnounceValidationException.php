<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class AnnounceValidationException extends Exception
{
    /**
     * @var array
     */
    private $validationMessages;

    /**
     * @param string         $message
     * @param array          $validationMessages
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', array $validationMessages = [], int $code = 0, Throwable $previous = null)
    {
        $this->validationMessages = $validationMessages;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }
}
