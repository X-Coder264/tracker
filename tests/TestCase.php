<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\EnableForeignKeyConstraints;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Boot the testing helper traits.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();

        // Foreign key constraints are disabled by default for SQLite so we have to change that
        if (isset($uses[EnableForeignKeyConstraints::class])) {
            /** @var $this TestCase|EnableForeignKeyConstraints */
            $this->enableForeignKeys();
        }

        return $uses;
    }
}
