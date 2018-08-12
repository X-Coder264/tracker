<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Imdb\Title;
use Tests\TestCase;
use App\Services\IMDBManager;

class IMDBManagerTest extends TestCase
{
    /**
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->IMDBManager = $this->app->make(IMDBManager::class);
    }

    public function testGetTitleFromFullURL(): void
    {
        $title = $this->IMDBManager->getTitleFromFullURL('https://www.imdb.com/title/tt0468569/');
        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame('0468569', $title->imdbid());
        $this->assertSame(60 * 24, $title->cache_expire);
    }

    public function testGetTitleFromIMDBId(): void
    {
        $title = $this->IMDBManager->getTitleFromIMDBId('0468569');
        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame('0468569', $title->imdbid());
        $this->assertSame(60 * 24, $title->cache_expire);
    }

    public function testGetIMDBIdFromFullURL(): void
    {
        $imdbId = $this->IMDBManager->getIMDBIdFromFullURL('https://www.imdb.com/title/tt0468569/');
        $this->assertSame('0468569', $imdbId);
    }

    public function testGetPosterURLFromIMDBId(): void
    {
        $imdbId = '0468569';
        $url = 'https://some_image.jpg';
        $title = $this->createPartialMock(Title::class, ['photo']);
        $title->expects($this->once())->method('photo')->willReturn($url);
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getTitleFromIMDBId']);
        $imdbManager->expects($this->once())->method('getTitleFromIMDBId')->with($imdbId)->willReturn($title);
        $this->assertSame($url, $imdbManager->getPosterURLFromIMDBId($imdbId));
    }

    public function testGetPosterURLFromIMDBIdWhenThereIsNoPoster(): void
    {
        $imdbId = '0468569';
        $title = $this->createPartialMock(Title::class, ['photo']);
        $title->expects($this->once())->method('photo')->willReturn(false);
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getTitleFromIMDBId']);
        $imdbManager->expects($this->once())->method('getTitleFromIMDBId')->with($imdbId)->willReturn($title);
        $this->assertNull($imdbManager->getPosterURLFromIMDBId($imdbId));
    }
}
