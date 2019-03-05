<?php

declare(strict_types=1);

namespace App\Services\Announce;

use Generator;
use App\Services\Bencoder;
use Illuminate\Http\Response;
use App\Exceptions\ValidationException;
use Illuminate\Contracts\Config\Repository;
use App\Presenters\Announce\Response as AnnounceResponse;
use Illuminate\Contracts\Routing\ResponseFactory as IlluminateResponseFactory;

final class ResponseFactory
{
    /**
     * @var Bencoder
     */
    private $encoder;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        IlluminateResponseFactory $responseFactory,
        Repository $config,
        Bencoder $encoder
    ) {
        $this->encoder = $encoder;
        $this->config = $config;
        $this->responseFactory = $responseFactory;
    }

    private function convertToResponse(array $data): Response
    {
        return $this->responseFactory
            ->make(
                $this->encoder->encode($data)
            )->header('Content-Type', 'text/plain');
    }

    public function validationError(ValidationException $exception): Response
    {
        $response['failure reason'] = implode(' ', $exception->validationMessages());

        if (true === $exception->neverRetry()) {
            $response['retry in'] = 'never';
        }

        return $this->convertToResponse($response);
    }

    private function commonSuccessPart(AnnounceResponse $response): array
    {
        $data['interval'] = $this->config->get('tracker.announce_interval') * 60;
        $data['min interval'] = $this->config->get('tracker.min_announce_interval') * 60;

        $data['complete'] = $response->seeders();
        $data['incomplete'] = $response->leechers();

        return $data;
    }

    public function compactSuccess(AnnounceResponse $response): Response
    {
        $data = $this->commonSuccessPart($response);

        $data += $this->compact($response->peers());

        return $this->convertToResponse($data);
    }

    public function nonCompactSuccess(AnnounceResponse $response): Response
    {
        $data = $this->commonSuccessPart($response);

        $data += $this->nonCompact($response->peers());

        return $this->convertToResponse($data);
    }

    public function compact(Generator $peers): array
    {
        $response['peers'] = '';

        // BEP 7 -> IPv6 peers support -> http://www.bittorrent.org/beps/bep_0007.html
        $response['peers6'] = '';

        foreach ($peers as $peer) {
            $peerIPAddress = inet_pton($peer->IP);
            $peerPort = pack('n*', $peer->port);

            if (true === (bool) $peer->isIPv6) {
                $response['peers6'] .= $peerIPAddress . $peerPort;
            } else {
                $response['peers'] .= $peerIPAddress . $peerPort;
            }
        }

        return $response;
    }

    public function nonCompact(Generator $peers): array
    {
        $response['peers'] = [];

        foreach ($peers as $peer) {
            // IPv6 peers are not separated for non-compact responses
            $response['peers'][] = [
                'peer id' => hex2bin($peer->peer_id),
                'ip'      => $peer->IP,
                'port'    => (int) $peer->port,
            ];
        }

        return $response;
    }
}
