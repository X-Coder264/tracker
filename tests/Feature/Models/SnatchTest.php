<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\User;
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
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->uploaded);
    }

    public function testDownloadedAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->downloaded);
    }

    public function testLeftAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getRawOriginal('left'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->left);
    }

    public function testSeedTimeAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getRawOriginal('seedTime'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->seedTime);
    }

    public function testLeechTimeAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getRawOriginal('leechTime'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->leechTime);
    }

    public function testTorrentRelationship(): void
    {
        $snatch = factory(Snatch::class)->create();

        $freshSnatch = $snatch->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshSnatch->torrent());
        $this->assertInstanceOf(Torrent::class, $freshSnatch->torrent);
        $this->assertSame($snatch->torrent->id, $freshSnatch->torrent->id);
    }

    public function testUserRelationship(): void
    {
        $snatch = factory(Snatch::class)->create();

        $freshSnatch = $snatch->fresh();
        $this->assertInstanceOf(BelongsTo::class, $freshSnatch->user());
        $this->assertInstanceOf(User::class, $freshSnatch->user);
        $this->assertSame($snatch->user->id, $freshSnatch->user->id);
    }
}
