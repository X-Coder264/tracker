<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use App\Services\StatisticsManager;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatisticsManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var StatisticsManager
     */
    private $statisticsManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->statisticsManager = $this->app->make(StatisticsManager::class);
        $this->connection = $this->app->make(Connection::class);

        $this->connection->enableQueryLog();
    }

    public function testGetPeersCount(): void
    {
        factory(Peer::class, 5)->create();
        $this->assertSame(5, $this->statisticsManager->getPeersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(5, $this->statisticsManager->getPeersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetSeedersCount(): void
    {
        factory(Peer::class, 1)->states('seeder')->create();
        factory(Peer::class, 3)->states('leecher')->create();
        $this->assertSame(1, $this->statisticsManager->getSeedersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(1, $this->statisticsManager->getSeedersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetLeechersCount(): void
    {
        factory(Peer::class, 1)->states('seeder')->create();
        factory(Peer::class, 3)->states('leecher')->create();
        $this->assertSame(3, $this->statisticsManager->getLeechersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(3, $this->statisticsManager->getLeechersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetUsersCount(): void
    {
        factory(User::class, 2)->create();
        $this->assertSame(2, $this->statisticsManager->getUsersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(2, $this->statisticsManager->getUsersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetBannedUsersCount(): void
    {
        factory(User::class, 2)->create();
        factory(User::class, 1)->states('banned')->create();
        $this->assertSame(1, $this->statisticsManager->getBannedUsersCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(1, $this->statisticsManager->getBannedUsersCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetTorrentsCount(): void
    {
        factory(Torrent::class, 1)->states('alive')->create();
        factory(Torrent::class, 2)->states('dead')->create();
        $this->assertSame(3, $this->statisticsManager->getTorrentsCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(3, $this->statisticsManager->getTorrentsCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }

    public function testGetDeadTorrentsCount(): void
    {
        factory(Torrent::class, 1)->states('alive')->create();
        factory(Torrent::class, 2)->states('dead')->create();
        $this->assertSame(2, $this->statisticsManager->getDeadTorrentsCount());
        $beforeQueryLog = $this->connection->getQueryLog();
        $this->assertSame(2, $this->statisticsManager->getDeadTorrentsCount());
        $afterQueryLog = $this->connection->getQueryLog();
        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }
}
