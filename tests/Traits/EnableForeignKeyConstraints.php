<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Database\DatabaseManager;

trait EnableForeignKeyConstraints
{
    /**
     * Enables foreign key constraints (which are disabled by default for SQLite).
     */
    public function enableForeignKeys(): void
    {
        /** @var DatabaseManager $db */
        $db = $this->app->make('db');
        $db->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}
