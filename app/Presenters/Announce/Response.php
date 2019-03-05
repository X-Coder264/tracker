<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use Generator;

final class Response
{
    /**
     * @var Generator
     */
    private $peers;

    /**
     * @var int
     */
    private $seeders;

    /**
     * @var int
     */
    private $leechers;

    public function __construct(Generator $peers, int $seeders, int $leechers)
    {
        $this->peers = $peers;
        $this->seeders = $seeders;
        $this->leechers = $leechers;
    }

    public function peers(): Generator
    {
        return $this->peers;
    }

    public function seeders(): int
    {
        return $this->seeders;
    }

    public function leechers(): int
    {
        return $this->leechers;
    }
}
