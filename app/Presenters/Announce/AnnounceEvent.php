<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use InvalidArgumentException;

final class AnnounceEvent
{
    private bool $started;
    private bool $stopped;
    private bool $completed;
    private bool $regularUpdate;

    public function __construct(?string $event)
    {
        if (null === $event) {
            $this->started = false;
            $this->stopped = false;
            $this->completed = false;
            $this->regularUpdate = true;
        } elseif ('started' === $event) {
            $this->started = true;
            $this->stopped = false;
            $this->completed = false;
            $this->regularUpdate = false;
        } elseif ('stopped' === $event) {
            $this->started = false;
            $this->stopped = true;
            $this->completed = false;
            $this->regularUpdate = false;
        } elseif ('completed' === $event) {
            $this->started = false;
            $this->stopped = false;
            $this->completed = true;
            $this->regularUpdate = false;
        } else {
            throw new InvalidArgumentException(sprintf('The given event name "%s" is invalid.', $event));
        }
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function isRegularUpdate(): bool
    {
        return $this->regularUpdate;
    }
}
