<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Snatch;
use App\Models\Torrent;
use Facades\App\Services\SizeFormatter;
use Facades\App\Services\SecondsDurationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnatchTest extends TestCase
{
    use RefreshDatabase;

    public function testUploadedAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->uploaded);
    }

    public function testDownloadedAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->downloaded);
    }

    public function testLeftAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($snatch->getOriginal('left'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->left);
    }

    public function testSeedTimeAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getOriginal('seedTime'))->andReturn($returnValue);
        $this->assertSame($returnValue, $snatch->seedTime);
    }

    public function testLeechTimeAccessor(): void
    {
        factory(Snatch::class)->create();
        $snatch = Snatch::firstOrFail();
        $returnValue = '500 MB';
        SecondsDurationFormatter::shouldReceive('format')->once()->with($snatch->getOriginal('leechTime'))->andReturn($returnValue);
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
