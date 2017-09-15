<?php

declare(strict_types=1);

namespace App\Services;

class SecondsDurationFormatter
{
    const SECOND = 's';

    const MINUTE = 'm';

    const HOUR = 'h';

    const DAY = 'd';

    protected $map = [
        self::SECOND => 60,
        self::MINUTE => 60,
        self::HOUR => 24,
        self::DAY => 0,
    ];

    public function format(int $seconds): string
    {
        if (0 === $seconds) {
            return '-';
        }

        $time = [];

        $remainingTimeToCalculate = $seconds;

        foreach ($this->map as $unit => $value) {
            if ($value > 1) {
                $calculatedValueForUnit = $remainingTimeToCalculate % $value;
                $remainingTimeToCalculate = floor($remainingTimeToCalculate / $value);
            } else {
                $calculatedValueForUnit = $remainingTimeToCalculate;
            }

            $time[$unit] = (int) $calculatedValueForUnit;
        }

        if (0 !== $time[self::DAY]) {
            return sprintf('%dd %02d:%02d:%02d', $time[self::DAY], $time[self::HOUR], $time[self::MINUTE], $time[self::SECOND]);
        }

        if (0 !== $time[self::HOUR]) {
            return sprintf('%d:%02d:%02d', $time[self::HOUR], $time[self::MINUTE], $time[self::SECOND]);
        }

        return sprintf('%d:%02d', $time[self::MINUTE], $time[self::SECOND]);
    }
}
