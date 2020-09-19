<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\StatisticsManager;
use Database\Factories\PeerFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StatisticsManagerTest extends TestCase
{
    use DatabaseTransactions;

    private StatisticsManager $statisticsManager;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statisticsManager = $this->app->make(StatisticsManager::class);
        $this->connection = $this->app->make(Connection::class);

        $this->connection->enableQueryLog();
    }

    public function testGetPeersCount(): void
    {
        PeerFactory::new()->count(5)->create();
        $this->assertSame(5, $this->statisticsManager->getPeersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(5, $this->statisticsManager->getPeersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetSeedersCount(): void
    {
        PeerFactory::new()->count(1)->seeder()->create();
        PeerFactory::new()->count(3)->leecher()->create();
        $this->assertSame(1, $this->statisticsManager->getSeedersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(1, $this->statisticsManager->getSeedersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetLeechersCount(): void
    {
        PeerFactory::new()->count(1)->seeder()->create();
        PeerFactory::new()->count(3)->leecher()->create();
        $this->assertSame(3, $this->statisticsManager->getLeechersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(3, $this->statisticsManager->getLeechersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetUsersCount(): void
    {
        UserFactory::new()->count(2)->create();
        $this->assertSame(2, $this->statisticsManager->getUsersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(2, $this->statisticsManager->getUsersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetBannedUsersCount(): void
    {
        UserFactory::new()->count(2)->create();
        UserFactory::new()->count(1)->banned()->create();
        $this->assertSame(1, $this->statisticsManager->getBannedUsersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(1, $this->statisticsManager->getBannedUsersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetTorrentsCount(): void
    {
        TorrentFactory::new()->alive()->create();
        TorrentFactory::new()->count(2)->dead()->create();
        $this->assertSame(3, $this->statisticsManager->getTorrentsCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(3, $this->statisticsManager->getTorrentsCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetDeadTorrentsCount(): void
    {
        TorrentFactory::new()->alive()->create();
        TorrentFactory::new()->count(2)->dead()->create();
        $this->assertSame(2, $this->statisticsManager->getDeadTorrentsCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(2, $this->statisticsManager->getDeadTorrentsCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetTotalTorrentSize(): void
    {
        TorrentFactory::new()->create(['size' => 400]);
        TorrentFactory::new()->create(['size' => 650]);
        $this->assertSame(1050, $this->statisticsManager->getTotalTorrentSize());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(1050, $this->statisticsManager->getTotalTorrentSize());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }
}
