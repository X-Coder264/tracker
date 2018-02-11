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
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[EnableForeignKeyConstraints::class])) {
            /* @var $this TestCase|EnableForeignKeyConstraints */
            $this->enableForeignKeys();
        }

        return parent::setUpTraits();
    }
}
