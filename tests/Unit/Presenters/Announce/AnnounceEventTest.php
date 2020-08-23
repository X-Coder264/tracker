<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters\Announce;

use App\Presenters\Announce\AnnounceEvent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AnnounceEventTest extends TestCase
{
    public function testStartedEventCreation(): void
    {
        $event = new AnnounceEvent('started');

        $this->assertTrue($event->isStarted());
        $this->assertFalse($event->isStopped());
        $this->assertFalse($event->isCompleted());
        $this->assertFalse($event->isRegularUpdate());
    }

    public function testStoppedEventCreation(): void
    {
        $event = new AnnounceEvent('stopped');

        $this->assertFalse($event->isStarted());
        $this->assertTrue($event->isStopped());
        $this->assertFalse($event->isCompleted());
        $this->assertFalse($event->isRegularUpdate());
    }

    public function testCompletedEventCreation(): void
    {
        $event = new AnnounceEvent('completed');

        $this->assertFalse($event->isStarted());
        $this->assertFalse($event->isStopped());
        $this->assertTrue($event->isCompleted());
        $this->assertFalse($event->isRegularUpdate());
    }

    public function testRegularUpdateEventCreation(): void
    {
        $event = new AnnounceEvent(null);

        $this->assertFalse($event->isStarted());
        $this->assertFalse($event->isStopped());
        $this->assertFalse($event->isCompleted());
        $this->assertTrue($event->isRegularUpdate());
    }

    public function testEventCreationWithInvalidEventNameThrowsAnException(): void
    {
        $invalidEventName = 'foo';

        $this->expectExceptionObject(new InvalidArgumentException('The given event name "foo" is invalid.'));

        new AnnounceEvent($invalidEventName);
    }

    public function testEventCreationWithEmptyEventNameThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('The given event name "" is invalid.'));

        new AnnounceEvent('');
    }
}
