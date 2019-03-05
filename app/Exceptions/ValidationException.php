<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $validationMessages;

    private $neverRetry;

    protected function __construct(array $messages, bool $neverRetry = false)
    {
        $this->validationMessages = $messages;
        $this->neverRetry = $neverRetry;

        parent::__construct('Announce validation error', 0, null);
    }

    public static function single(string $message, bool $neverRetry = false): self
    {
        return new static([$message], $neverRetry);
    }

    public static function multiple(string ...$messages): self
    {
        return new static($messages);
    }

    public function validationMessages(): array
    {
        return $this->validationMessages;
    }

    /**
     * BEP 31 http://www.bittorrent.org/beps/bep_0031.html.
     */
    public function neverRetry(): bool
    {
        return $this->neverRetry;
    }
}
