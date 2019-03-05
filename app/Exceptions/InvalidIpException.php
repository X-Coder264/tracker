<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;
use Throwable;

class InvalidIpException extends InvalidArgumentException
{
    protected function __construct(string $message = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function badIpV4(Throwable $previous = null): self
    {
        return new static('Bad Ip v4 provided', $previous);
    }

    public static function badIpV6(Throwable $previous = null): self
    {
        return new static('Bad Ip v6 provided', $previous);
    }

    public static function badIp(Throwable $previous = null): self
    {
        return new static('Bad Ip provided', $previous);
    }
}
