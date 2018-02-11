<?php

namespace Tests\Traits;

use Illuminate\Database\DatabaseManager;

trait EnableForeignKeyConstraints
{
    /**
     * Enables foreign key constraints (which are disabled by default for SQLite which is used in the test env).
     */
    public function enableForeignKeys(): void
    {
        /* @var DatabaseManager $db */
        $db = app()->make('db');
        $db->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}
