<?php

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

        if (isset($uses[EnableForeignKeyConstraints::class])) {
            /* @var $this TestCase|EnableForeignKeyConstraints */
            $this->enableForeignKeys();
        }

        return $uses;
    }
}
