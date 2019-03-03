<?php

declare(strict_types=1);

namespace App\Enumerations;

final class AnnounceEvent
{
    const STARTED = 'started';

    const STOPPED = 'stopped';

    const COMPLETED = 'completed';

    // event is not provided, so it's missing
    const MISSING = null;
}
