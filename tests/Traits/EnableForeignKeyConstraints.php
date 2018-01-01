<?php

namespace Tests\Traits;

trait EnableForeignKeyConstraints
{
    /**
     * Enables foreign key constraints (which are disabled by default for SQLite DBs which is used in the test env.
     */
    public function enableForeignKeys(): void
    {
        $db = app()->make('db');
        $db->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}
