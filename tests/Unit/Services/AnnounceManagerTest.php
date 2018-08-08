<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use stdClass;
use Tests\TestCase;
use ReflectionClass;
use App\Http\Models\Peer;
use App\Services\Bencoder;
use App\Http\Models\PeerIP;
use App\Http\Models\Torrent;
use App\Services\AnnounceManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class AnnounceManagerTest extends TestCase
{
    public function testIPv4AddressValidation()
    {
        $announceManager = new AnnounceManager(
            new Bencoder(),
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('validateIPv4Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceManager, ['95.152.44.55']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44.555']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44.']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44']));
        $this->assertFalse($method->invokeArgs($announceManager, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
    }

    public function testIPv6AddressValidation()
    {
        $announceManager = new AnnounceManager(
            new Bencoder(),
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('validateIPv6Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceManager, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
        $this->assertTrue($method->invokeArgs($announceManager, ['2001:3452:4952:2837::']));
        $this->assertTrue($method->invokeArgs($announceManager, ['FE80::0202:B3FF:FE1E:8329']));
        $this->assertTrue($method->invokeArgs($announceManager, ['1200:0000:AB00:1234:0000:2552:7777:1313']));
        $this->assertTrue($method->invokeArgs($announceManager, ['21DA:D3:0:2F3B:2AA:FF:FE28:9C5A']));
        $this->assertFalse($method->invokeArgs($announceManager, ['1200::AB00:1234::2552:7777:1313']));
        $this->assertFalse($method->invokeArgs($announceManager, ['[2001:db8:0:1]:80']));
        $this->assertFalse($method->invokeArgs($announceManager, ['1200:0000:AB00:1234:O000:2552:7777:1313']));
    }

    public function testErrorResponseWithStringParameter()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = 'Error xyz.';
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $error]))
            ->willReturn($returnValue);

        $announceManager = new AnnounceManager(
            $encoder,
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
    }

    public function testErrorResponseWithArrayParameter()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = ['Error X.', 'Error Y.'];
        $errorMessage = 'Error X. Error Y.';
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $errorMessage]))
            ->willReturn($returnValue);

        $announceManager = new AnnounceManager(
            $encoder,
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
    }

    public function testCompactResponse()
    {
        $torrent = factory(Torrent::class)->make(['seeders' => 1, 'leechers' => 0, 'uploader_id' => 1]);
        $peer = factory(Peer::class)->make(['torrent_id' => 1, 'user_id' => 1, 'seeder' => true]);

        $IPs = new Collection([
            factory(PeerIP::class)->make(['peerID' => $peer, 'IP' => '95.152.44.55', 'isIPv6' => false, 'port' => 55555]),
            factory(PeerIP::class)->make(['peerID' => $peer, 'IP' => '2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2', 'isIPv6' => true, 'port' => 60000]),
        ]);

        $peerOne = new stdClass();
        $peerOne->peer_id = $peer->peer_id;
        $peerOne->seeder = $peer->seeder;
        $peerOne->IP = $IPs[0]->IP;
        $peerOne->port = $IPs[0]->port;
        $peerOne->isIPv6 = $IPs[0]->isIPv6;

        $peerTwo = new stdClass();
        $peerTwo->peer_id = $peer->peer_id;
        $peerTwo->seeder = $peer->seeder;
        $peerTwo->IP = $IPs[1]->IP;
        $peerTwo->port = $IPs[1]->port;
        $peerTwo->isIPv6 = $IPs[1]->isIPv6;

        $peers = Collection::make([$peerOne, $peerTwo]);

        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();

        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(
                [
                    'interval'     => 2400,
                    'min interval' => 60,
                    'complete'     => 0,
                    'incomplete'   => 0,
                    'peers'        => inet_pton('95.152.44.55') . pack('n*', 55555),
                    'peers6'       => inet_pton('2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2') . pack('n*', 60000),
                ]
            ))
            ->willReturn($returnValue);

        $announceManager = $this->getMockBuilder(AnnounceManager::class)
            ->setConstructorArgs(
                [
                    $encoder,
                    $this->app->make(DatabaseManager::class),
                    $this->app->make(CacheManager::class),
                    $this->app->make(ValidationFactory::class),
                    $this->app->make(Translator::class),
                ]
            )
            ->setMethods(['getPeers'])
            ->getMock();
        $announceManager->expects($this->once())
            ->method('getPeers')
            ->willReturn($peers);

        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $reflectionMethod = $reflectionClass->getMethod('compactResponse');
        $reflectionMethod->setAccessible(true);
        $reflectionProperty = $reflectionClass->getProperty('torrent');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($announceManager, $torrent);
        $reflectionProperty = $reflectionClass->getProperty('seeder');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($announceManager, true);
        $this->assertSame($returnValue, $reflectionMethod->invoke($announceManager));
    }

    public function testNonCompactResponse()
    {
        $torrent = factory(Torrent::class)->make(['seeders' => 1, 'leechers' => 0, 'uploader_id' => 1]);
        $peer = factory(Peer::class)->make(
            [
                'torrent_id' => 1,
                'user_id'    => 1,
                'seeder'     => true,
                'peer_id'    => '2d7142333345302d64354e334474384672517776',
            ]
        );

        $IPs = new Collection([
            factory(PeerIP::class)->make(
                [
                    'peerID' => $peer,
                    'IP'     => '95.152.44.55',
                    'isIPv6' => false,
                    'port'   => 55555,
                ]
            ),
            factory(PeerIP::class)->make(
                [
                    'peerID' => $peer,
                    'IP'     => '2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2',
                    'isIPv6' => true,
                    'port'   => 60000,
                ]
            ),
        ]);

        $peerOne = new stdClass();
        $peerOne->peer_id = $peer->peer_id;
        $peerOne->seeder = $peer->seeder;
        $peerOne->IP = $IPs[0]->IP;
        $peerOne->port = $IPs[0]->port;
        $peerOne->isIPv6 = $IPs[0]->isIPv6;

        $peerTwo = new stdClass();
        $peerTwo->peer_id = $peer->peer_id;
        $peerTwo->seeder = $peer->seeder;
        $peerTwo->IP = $IPs[1]->IP;
        $peerTwo->port = $IPs[1]->port;
        $peerTwo->isIPv6 = $IPs[1]->isIPv6;

        $peers = Collection::make([$peerOne, $peerTwo]);

        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();

        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(
                [
                    'interval'     => 2400,
                    'min interval' => 60,
                    'complete'     => 0,
                    'incomplete'   => 0,
                    'peers'        => [
                        [
                            'peer id' => '-qB33E0-d5N3Dt8FrQwv',
                            'ip'      => '95.152.44.55',
                            'port'    => 55555,
                        ],
                        [
                            'peer id' => '-qB33E0-d5N3Dt8FrQwv',
                            'ip'      => '2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2',
                            'port'    => 60000,
                        ],
                    ],
                ]
            ))
            ->willReturn($returnValue);

        $announceManager = $this->getMockBuilder(AnnounceManager::class)
            ->setConstructorArgs(
                [
                    $encoder,
                    $this->app->make(DatabaseManager::class),
                    $this->app->make(CacheManager::class),
                    $this->app->make(ValidationFactory::class),
                    $this->app->make(Translator::class),
                ]
            )
            ->setMethods(['getPeers'])
            ->getMock();
        $announceManager->expects($this->once())
            ->method('getPeers')
            ->willReturn($peers);

        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $reflectionMethod = $reflectionClass->getMethod('nonCompactResponse');
        $reflectionMethod->setAccessible(true);
        $reflectionProperty = $reflectionClass->getProperty('torrent');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($announceManager, $torrent);
        $reflectionProperty = $reflectionClass->getProperty('seeder');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($announceManager, true);
        $this->assertSame($returnValue, $reflectionMethod->invoke($announceManager));
    }
}
