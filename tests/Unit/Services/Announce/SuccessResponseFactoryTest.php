<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Announce;

use App\Presenters\Announce\Response\Peer;
use App\Presenters\Announce\Response\PeersCount;
use App\Services\Announce\SuccessResponseFactory;
use App\Services\Bencoder;
use Illuminate\Contracts\Config\Repository;
use PHPUnit\Framework\TestCase;

final class SuccessResponseFactoryTest extends TestCase
{
    /**
     * @param Peer[] $peers
     *
     * @dataProvider compactResponseDataProvider
     */
    public function testSuccessCompactResponseCreation(
        array $peers,
        PeersCount $peersCount,
        int $announceIntervalInMinutes,
        int $minAnnounceIntervalInMinutes,
        array $expectedDataToEncode
    ): void {
        $config = $this->createMock(Repository::class);
        $config->method('get')
            ->withConsecutive(['tracker.announce_interval'], ['tracker.min_announce_interval'])
            ->willReturnOnConsecutiveCalls($announceIntervalInMinutes, $minAnnounceIntervalInMinutes);

        $encoder = $this->createMock(Bencoder::class);
        $encoder->expects($this->once())->method('encode')->with($expectedDataToEncode)->willReturn('foo');

        $this->assertSame('foo', (new SuccessResponseFactory($encoder, $config))->getCompactResponse($peers, $peersCount));
    }

    /**
     * @param Peer[] $peers
     *
     * @dataProvider nonCompactResponseDataProvider
     */
    public function testSuccessNonCompactResponseCreation(
        array $peers,
        PeersCount $peersCount,
        int $announceIntervalInMinutes,
        int $minAnnounceIntervalInMinutes,
        array $expectedDataToEncode
    ): void {
        $config = $this->createMock(Repository::class);
        $config->method('get')
            ->withConsecutive(['tracker.announce_interval'], ['tracker.min_announce_interval'])
            ->willReturnOnConsecutiveCalls($announceIntervalInMinutes, $minAnnounceIntervalInMinutes);

        $encoder = $this->createMock(Bencoder::class);
        $encoder->expects($this->once())->method('encode')->with($expectedDataToEncode)->willReturn('foo');

        $this->assertSame('foo', (new SuccessResponseFactory($encoder, $config))->getNonCompactResponse($peers, $peersCount));
    }

    public function compactResponseDataProvider(): iterable
    {
        yield 'no peers response' => [
            [],
            new PeersCount(0, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 0,
                'incomplete' => 0,
                'peers' => '',
                'peers6' => '',
            ],
        ];

        yield 'one IPv4 peer response' => [
            [new Peer('192.168.1.1', false, 55000, 'abc')],
            new PeersCount(1, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 0,
                'peers' => $this->getPackedInAddrRepresentationOfIpAddress('192.168.1.1') . $this->getBinaryPortRepresentation(55000),
                'peers6' => '',
            ],
        ];

        $expectedPeersString = $this->getPackedInAddrRepresentationOfIpAddress('192.168.1.1') .
            $this->getBinaryPortRepresentation(55000) .
            $this->getPackedInAddrRepresentationOfIpAddress('192.168.1.2') .
            $this->getBinaryPortRepresentation(65000);

        yield 'two IPv4 peer response' => [
            [
                new Peer('192.168.1.1', false, 55000, 'aac'),
                new Peer('192.168.1.2', false, 65000, 'bbc'),
            ],
            new PeersCount(1, 1),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 1,
                'peers' => $expectedPeersString,
                'peers6' => '',
            ],
        ];

        yield 'one IPv6 peer response' => [
            [new Peer('2001:0db8:0a0b:12f0:0000:0000:0000:0001', true, 55000, 'abd')],
            new PeersCount(1, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 0,
                'peers' => '',
                'peers6' => $this->getPackedInAddrRepresentationOfIpAddress('2001:0db8:0a0b:12f0:0000:0000:0000:0001') . $this->getBinaryPortRepresentation(55000),
            ],
        ];

        $expectedPeersString = $this->getPackedInAddrRepresentationOfIpAddress('2001:0db8:0a0b:12f0:0000:0000:0000:0001') .
            $this->getBinaryPortRepresentation(35000) .
            $this->getPackedInAddrRepresentationOfIpAddress('2001:0db8:0a0b:12f0:0000:0000:0000:0002') .
            $this->getBinaryPortRepresentation(45000);

        yield 'two IPv6 peer response' => [
            [
                new Peer('2001:0db8:0a0b:12f0:0000:0000:0000:0001', true, 35000, 'abc'),
                new Peer('2001:0db8:0a0b:12f0:0000:0000:0000:0002', true, 45000, 'bac'),
            ],
            new PeersCount(2, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 2,
                'incomplete' => 0,
                'peers' => '',
                'peers6' => $expectedPeersString,
            ],
        ];

        yield 'mixed IPv4 and IPv6 peer response' => [
            [
                new Peer('192.168.1.1', false, 33500, 'abc'),
                new Peer('2001:0db8:0a0b:12f0:0000:0000:0000:0003', true, 37500, 'bbb'),
            ],
            new PeersCount(1, 1),
            20,
            15,
            [
                'interval' => 1200,
                'min interval' => 900,
                'complete' => 1,
                'incomplete' => 1,
                'peers' => $this->getPackedInAddrRepresentationOfIpAddress('192.168.1.1') . $this->getBinaryPortRepresentation(33500),
                'peers6' => $this->getPackedInAddrRepresentationOfIpAddress('2001:0db8:0a0b:12f0:0000:0000:0000:0003') . $this->getBinaryPortRepresentation(37500),
            ],
        ];
    }

    public function nonCompactResponseDataProvider(): iterable
    {
        yield 'no peers response' => [
            [],
            new PeersCount(0, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 0,
                'incomplete' => 0,
                'peers' => [],
            ],
        ];

        yield 'one IPv4 peer response' => [
            [new Peer('192.168.1.1', false, 55000, '6578ab')],
            new PeersCount(1, 0),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 0,
                'peers' => [
                    [
                        'peer id' => hex2bin('6578ab'),
                        'ip'      => '192.168.1.1',
                        'port'    => 55000,
                    ],
                ],
            ],
        ];

        yield 'two IPv4 peer response' => [
            [
                new Peer('192.168.1.1', false, 45000, '6578ac'),
                new Peer('192.168.1.2', false, 25000, '6578ad'),
            ],
            new PeersCount(1, 1),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 1,
                'peers' => [
                    [
                        'peer id' => hex2bin('6578ac'),
                        'ip'      => '192.168.1.1',
                        'port'    => 45000,
                    ],
                    [
                        'peer id' => hex2bin('6578ad'),
                        'ip'      => '192.168.1.2',
                        'port'    => 25000,
                    ],
                ],
            ],
        ];

        yield 'one IPv6 peer response' => [
            [new Peer('2001:0db8:0a0b:12f0:0000:0000:0000:0003', true, 22300, '6578ae')],
            new PeersCount(0, 1),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 0,
                'incomplete' => 1,
                'peers' => [
                    [
                        'peer id' => hex2bin('6578ae'),
                        'ip'      => '2001:0db8:0a0b:12f0:0000:0000:0000:0003',
                        'port'    => 22300,
                    ],
                ],
            ],
        ];

        yield 'two IPv6 peer response' => [
            [
                new Peer('2004:0db8:0a0b:12f0:0000:0000:0000:0001', true, 55009, '3578ac'),
                new Peer('2002:0db8:0a0b:12f0:0000:0000:0000:0004', true, 28005, '3178dc'),
            ],
            new PeersCount(1, 1),
            45,
            30,
            [
                'interval' => 2700,
                'min interval' => 1800,
                'complete' => 1,
                'incomplete' => 1,
                'peers' => [
                    [
                        'peer id' => hex2bin('3578ac'),
                        'ip'      => '2004:0db8:0a0b:12f0:0000:0000:0000:0001',
                        'port'    => 55009,
                    ],
                    [
                        'peer id' => hex2bin('3178dc'),
                        'ip'      => '2002:0db8:0a0b:12f0:0000:0000:0000:0004',
                        'port'    => 28005,
                    ],
                ],
            ],
        ];

        yield 'mixed IPv4 and IPv6 peer response' => [
            [
                new Peer('192.168.1.8', false, 33943, '3578ac'),
                new Peer('2011:0db8:0a0c:12d0:0000:0040:0200:0003', true, 47657, '3178dc'),
            ],
            new PeersCount(0, 2),
            20,
            15,
            [
                'interval' => 1200,
                'min interval' => 900,
                'complete' => 0,
                'incomplete' => 2,
                'peers' => [
                    [
                        'peer id' => hex2bin('3578ac'),
                        'ip'      => '192.168.1.8',
                        'port'    => 33943,
                    ],
                    [
                        'peer id' => hex2bin('3178dc'),
                        'ip'      => '2011:0db8:0a0c:12d0:0000:0040:0200:0003',
                        'port'    => 47657,
                    ],
                ],
            ],
        ];
    }

    private function getPackedInAddrRepresentationOfIpAddress(string $ip): string
    {
        return inet_pton($ip);
    }

    private function getBinaryPortRepresentation(int $port): string
    {
        return pack('n*', $port);
    }
}
