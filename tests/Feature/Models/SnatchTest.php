<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\User;
use Database\Factories\SnatchFactory;
use Facades\App\Services\SecondsDurationFormatter;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SnatchTest extends TestCase
{
    use DatabaseTransactions;

    public function testUploadedAccessor(): void
    {
        SnatchFactory::new()->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->uploaded);
    }

    public function testDownloadedAccessor(): void
    {
        SnatchFactory::new()->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->downloaded);
    }

    public function testLeftAccessor(): void
    {
        SnatchFactory::new()->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('left'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->left);
    }

    public function testSeedTimeAccessor(): void
    {
        SnatchFactory::new()->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getRawOriginal('seed_time'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->seed_time);
    }

    public function testLeechTimeAccessor(): void
    {
        SnatchFactory::new()->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getRawOriginal('leech_time'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->leech_time);
    }

    public function testTorrentRelationship(): void
    {
        $snatch = SnatchFactory::new()->create();

        $freshSnatch = $snatch->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshSnatch->torrent());
        $this->assertInstanceOf(Torrent::class, $freshSnatch->torrent);
        $this->assertSame($snatch->torrent->id, $freshSnatch->torrent->id);
    }

    public function testUserRelationship(): void
    {
        $snatch = SnatchFactory::new()->create();

        $freshSnatch = $snatch->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshSnatch->user());
        $this->assertInstanceOf(User::class, $freshSnatch->user);
        $this->assertSame($snatch->user->id, $freshSnatch->user->id);
    }
}
