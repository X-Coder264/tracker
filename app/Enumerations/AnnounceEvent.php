<?php

declare(strict_types=1);

namespace App\Enumerations;

final class AnnounceEvent
{
    const STARTED = 'started';

    const STOPPED = 'stopped';

    const COMPLETED = 'completed';

    /**
     * Not existing event (BEP 3) but it's representation of
     * announce event when it's not present or it's empty.
     * Announcement done in regular intervals.
     */
    const PING = null;
}
