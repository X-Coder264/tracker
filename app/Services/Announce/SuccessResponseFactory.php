<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Presenters\Announce\Response\Peer;
use App\Presenters\Announce\Response\PeersCount;
use App\Services\Bencoder;
use Illuminate\Contracts\Config\Repository;

final class SuccessResponseFactory
{
    private Bencoder $encoder;
    private Repository $config;

    public function __construct(Bencoder $encoder, Repository $config)
    {
        $this->encoder = $encoder;
        $this->config = $config;
    }

    /**
     * @param Peer[] $peers
     */
    public function getCompactResponse(array $peers, PeersCount $peersCount): string
    {
        $response = $this->getCommonResponsePart($peersCount);

        $response['peers'] = '';

        // BEP 7 -> IPv6 peers support -> http://www.bittorrent.org/beps/bep_0007.html
        $response['peers6'] = '';

        foreach ($peers as $peer) {
            $peerIPAddress = inet_pton($peer->getIp());
            $peerPort = pack('n*', $peer->getPort());

            if ($peer->isIPv6()) {
                $response['peers6'] .= $peerIPAddress . $peerPort;
            } else {
                $response['peers'] .= $peerIPAddress . $peerPort;
            }
        }

        return $this->encoder->encode($response);
    }

    /**
     * @param Peer[] $peers
     */
    public function getNonCompactResponse(array $peers, PeersCount $peersCount): string
    {
        $response = $this->getCommonResponsePart($peersCount);
        $response['peers'] = [];

        foreach ($peers as $peer) {
            // IPv6 peers are not separated for non-compact responses
            $response['peers'][] = [
                'peer id' => hex2bin($peer->getId()),
                'ip'      => $peer->getIp(),
                'port'    => $peer->getPort(),
            ];
        }

        return $this->encoder->encode($response);
    }

    private function getCommonResponsePart(PeersCount $peersCount): array
    {
        $response['interval'] = $this->config->get('tracker.announce_interval') * 60;
        $response['min interval'] = $this->config->get('tracker.min_announce_interval') * 60;

        $response['complete'] = $peersCount->getSeedersCount();
        $response['incomplete'] = $peersCount->getLeechersCount();

        return $response;
    }
}
