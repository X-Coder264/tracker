<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Http\Models\Peer;
use App\Http\Models\Torrent;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AnnounceService
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var BencodingService
     */
    protected $encoder;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Peer|null
     */
    protected $peer = null;

    /**
     * @var Torrent
     */
    protected $torrent;

    /**
     * @var bool
     */
    protected $seeder;

    /**
     * @var int
     */
    protected $numberOfWantedPeers;

    /**
     * @var string|null
     */
    protected $ipv4Address = null;

    /**
     * @var string|null
     */
    protected $ipv6Address = null;

    /**
     * @var int|null
     */
    protected $ipv4Port = null;

    /**
     * @var int|null
     */
    protected $ipv6Port = null;

    /**
     * @param BencodingService $encoder
     */
    public function __construct(BencodingService $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function announce(Request $request): string
    {
        $this->request = $request;

        $event = $this->request->input('event');

        Storage::put('request.txt', print_r($request->all(), true));

        // info_hash and peer_id are validated separately because the Laravel validator uses
        // mb_strlen to get the length of the string which returns a wrong number
        // when used on those two properties so strlen must be used
        $validation = $this->validateInfoHashAndPeerID();
        if (null !== $validation) {
            return $validation;
        }
        if ('stopped' !== $event) {
            // in order to support IPv6 peers (BEP 7) a more complex IP validation logic was needed
            $validation = $this->validateAndSetIPAddress();
            if (null !== $validation) {
                return $validation;
            }

            $validation = $this->validateRequest();
            if (null !== $validation) {
                return $validation;
            }
        }

        $this->user = User::with('language')
                            ->where('passkey', '=', $this->request->input('passkey'))
                            ->select(['id', 'slug'])
                            ->first();

        $this->torrent = Torrent::where('infoHash', bin2hex($this->request->input('info_hash')))
                                ->select(['id', 'seeders', 'leechers'])
                                ->first();

        if ('started' !== $event) {
            $this->peer = Peer::where('peer_id', '=', bin2hex($this->request->input('peer_id')))
                ->where('torrent_id', '=', $this->torrent->id)
                ->where('user_id', '=', $this->user->id)
                ->first();
        }

        if ($this->request->has('numwant')) {
            $this->numberOfWantedPeers = (int) $this->request->input('numwant');
        } else {
            $this->numberOfWantedPeers = 50;
        }

        $left = (int) $this->request->input('left');
        $this->seeder = $left === 0 ? true : false;

        if ('started' === $event) {
            return $this->startedEventAnnounceResponse();
        } elseif ('stopped' === $event) {
            return $this->stoppedEventAnnounceResponse();
        } elseif ('completed' === $event && 0 === $left) {
            return $this->completedEventAnnounceResponse();
        }

        return $this->noEventAnnounceResponse();
    }

    /**
     * @return null|string
     */
    protected function validateInfoHashAndPeerID(): ?string
    {
        if ($this->request->has('info_hash')) {
            if (20 !== strlen($this->request->input('info_hash'))) {
                $errorMessage = __('messages.validation.variable.size', ['var' => 'info_hash']);
                return $this->announceErrorResponse($errorMessage);
            }
        } else {
            $errorMessage = __('messages.validation.variable.required', ['var' => 'info_hash']);
            return $this->announceErrorResponse($errorMessage);
        }

        if ($this->request->has('peer_id')) {
            if (20 !== strlen($this->request->input('peer_id'))) {
                $errorMessage = __('messages.validation.variable.size', ['var' => 'peer_id']);
                return $this->announceErrorResponse($errorMessage);
            }
        } else {
            $errorMessage = __('messages.validation.variable.required', ['var' => 'peer_id']);
            return $this->announceErrorResponse($errorMessage);
        }

        return null;
    }

    /**
     * @return null|string
     */
    protected function validateRequest(): ?string
    {
        $validator = Validator::make(
            $this->request->all(),
            [
                'passkey' => 'required|string|size:64',
                'port' => 'required|numeric',
                'uploaded' => 'required|numeric',
                'downloaded' => 'required|numeric',
                'left' => 'required|numeric',
                'numwant' => 'sometimes|numeric'
            ],
            [
                'passkey.required' => __('messages.validation.variable.required', ['var' => 'passkey']),
                'passkey.string' => __('messages.validation.variable.string', ['var' => 'passkey']),
                'passkey.size' => __('messages.validation.variable.size', ['var' => 'passkey']),
                'ip.required' => __('messages.validation.variable.required', ['var' => 'IP']),
                'ip.ip' => __('messages.validation.ip.ip', ['var' => 'IP']),
                'port.required' => __('messages.validation.variable.required', ['var' => 'port']),
                'port.numeric' => __('messages.validation.variable.port', ['port' => $this->request->input('port')]),
                'uploaded.required' => __('messages.validation.variable.required', ['var' => 'uploaded']),
                'uploaded.integer' => __('messages.validation.variable.integer', ['var' => $this->request->input('uploaded')]),
                'downloaded.required' => __('messages.validation.variable.required', ['var' => 'downloaded']),
                'downloaded.numeric' => __('messages.validation.variable.integer', ['var' => $this->request->input('downloaded')]),
                'left.required' => __('messages.validation.variable.required', ['var' => 'left']),
                'left.numeric' => __('messages.validation.variable.integer', ['var' => $this->request->input('left')]),
                'numwant.numeric' => __('messages.validation.variable.integer', ['var' => $this->request->input('numwant')]),
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->announceErrorResponse($errors->all());
        }

        return null;
    }

    /**
     * @return null|string
     */
    protected function validateAndSetIPAddress(): ?string
    {
        $this->ipv4Port = $this->request->input('port');
        $this->ipv6Port = $this->request->input('port');

        if ($this->request->has('ip')) {
            $ip = $this->request->input('ip');
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->ipv4Address = $ip;
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $this->ipv6Address = $ip;
            }
        }

        if ($this->request->has('ipv4')) {
            $ip = $this->request->input('ipv4');
            $explodedIPString = explode(':', $ip);
            // check if the ipv4 field has the IP address and the port
            // if it contains only the IP address the port is read from the port field
            if (2 === count($explodedIPString)) {
                if (filter_var($explodedIPString[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $this->ipv4Address = $explodedIPString[0];
                    $this->ipv4Port = (int) $explodedIPString[1];
                }
            } else {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $this->ipv4Address = $ip;
                }
            }
        }

        if ($this->request->has('ipv6')) {
            $ip = $this->request->input('ipv6');
            $explodedIPString = explode(':', $ip);
            // check if the ipv6 field has the IP address and the port
            // if it contains only the IP address the port is read from the port field
            if (4 <= count($explodedIPString) && '[' === $ip[0] && false !== strpos($ip, ']')) {
                $ip = str_replace(['[',']'], '', $ip);
                $ip = substr($ip, 0, strrpos($ip, ':'));
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $this->ipv6Address = $ip;
                    $this->ipv6Port = (int) substr($ip, strrpos($ip, ':') + 1);
                }
            } else {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $this->ipv6Address = $ip;
                }
            }
        }

        // the most secure way to get the real IP address because for example
        // uTorrent with Teredo enabled sends only an "IPv6" address even though the peer
        // has actually only an IPv4 address
        $ip = $this->request->getClientIp();
        //Storage::put('ip.txt', print_r($ip, true));

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->ipv4Address = $ip;
            //Storage::put('ipv4.txt', print_r($ip, true));
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->ipv6Address = $ip;
            //Storage::put('ipv6.txt', print_r($ip, true));
        }


        // if there is not at least one IP address set, return an error
        if (false === ((null !== $this->ipv4Address && null !== $this->ipv4Port) ||
                (null !== $this->ipv6Address && null !== $this->ipv6Port))) {
            return $this->announceErrorResponse('The IP or port was not sent.');
        }

        return null;
    }

    /**
     * Insert the peer IP address(es)
     */
    protected function insertPeerIPs(): void
    {
        if (false !== isset($this->ipv4Address) && false !== isset($this->ipv4Port)) {
            $this->peer->IPs()->updateOrCreate(
                [
                    'IP' => $this->ipv4Address,
                    'port' => $this->ipv4Port,
                    'isIPv6' => false,
                    'connectable' => $this->isPeerConnectable($this->ipv4Address, (int) $this->ipv4Port),
                ]
            );
        }

        if (false !== isset($this->ipv6Address) && false !== isset($this->ipv6Port)) {
            $this->peer->IPs()->updateOrCreate(
                [
                    'IP' => $this->ipv6Address,
                    'port' => $this->ipv6Port,
                    'isIPv6' => true,
                    'connectable' => $this->isPeerConnectable($this->ipv6Address, (int) $this->ipv6Port),
                ]
            );
        }
    }

    /**
     * Check if the peer is connectable
     * @param string $IP
     * @param int $port
     * @return bool
     */
    protected function isPeerConnectable(string $IP, int $port): bool
    {
        $sockres = @fsockopen($IP, $port, $errno, $errstr, 5);
        if (!$sockres) {
            return false;
        } else {
            @fclose($sockres);
            return true;
        }
    }

    /**
     * @return string
     */
    protected function startedEventAnnounceResponse(): string
    {
        // TODO: cast to int
        $this->peer = Peer::firstOrCreate(
            [
                'peer_id' => bin2hex($this->request->input('peer_id')),
                'torrent_id' => $this->torrent->id,
                'user_id' => $this->user->id,
            ],
            [
                'uploaded' => $this->request->input('uploaded'),
                'downloaded' => $this->request->input('downloaded'),
                'left' => $this->request->input('left'),
                'userAgent' => $this->request->userAgent(),
            ]
        );

        Storage::put('peerStartedEvent.txt', print_r($this->peer, true));

        $this->insertPeerIPs();

        Torrent::where('id', '=', $this->torrent->id)->update(['leechers' => $this->torrent->leechers + 1]);

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
    protected function stoppedEventAnnounceResponse(): string
    {
        $this->peer->delete();

        if (true === $this->seeder) {
            Torrent::where('id', '=', $this->torrent->id)->update(['seeders' => $this->torrent->seeders - 1]);
        } else {
            Torrent::where('id', '=', $this->torrent->id)->update(['leechers' => $this->torrent->leechers - 1]);
        }

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
    protected function completedEventAnnounceResponse(): string
    {
        // TODO: cast to int
        $this->peer->update(
            [
                'uploaded' => $this->request->input('uploaded'),
                'downloaded' => $this->request->input('downloaded'),
                'left' => 0,
                'finishedAt' => Carbon::now(),
                'userAgent' => $this->request->userAgent(),
            ]
        );

        $this->insertPeerIPs();

        Torrent::where('id', '=', $this->torrent->id)->update(
            [
                'seeders' => $this->torrent->seeders + 1,
                'leechers' => $this->torrent->leechers - 1
            ]
        );

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
    protected function noEventAnnounceResponse(): string
    {
        // TODO: cast to int
        $this->peer = Peer::updateOrCreate(
            [
                'peer_id' => bin2hex($this->request->input('peer_id')),
                'torrent_id' => $this->torrent->id,
                'user_id' => $this->user->id,
            ],
            [
                'uploaded' => $this->request->input('uploaded'),
                'downloaded' => $this->request->input('downloaded'),
                'left' => $this->request->input('left'),
                'userAgent' => $this->request->userAgent(),
            ]
        );

        Storage::put('peerNoEvent.txt', print_r($this->peer, true));

        $this->insertPeerIPs();

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
    protected function announceSuccessResponse(): string
    {
        $compact = $this->request->input('compact');
        // return compact response if the client wants a compact response or if the client did not
        // specify what kind of response it wants, else return non-compact response
        if (null === $compact || 1 === (int) $compact) {
            return $this->compactResponse();
        } else {
            return $this->nonCompactResponse();
        }
    }

    protected function compactResponse(): string
    {
        $response['interval'] = 40 * 60; // 40 minutes
        $response['min interval'] = 15 * 60; // 15 minutes
        $response['peers'] = '';
        // BEP 7 -> IPv6 peers support
        $response['peers6'] = '';

        if (true === $this->seeder) {
            $peers = Peer::with('IPs')
                            ->where('left', '!=', 0)
                            ->whereNull('finishedAt')
                            ->where('user_id', '!=', $this->user->id)
                            ->where('torrent_id', '=', $this->torrent->id)
                            ->limit($this->numberOfWantedPeers)
                            ->inRandomOrder()
                            ->select(['id', 'peer_id'])
                            ->get();
        } else {
            $peers = Peer::with('IPs')
                            ->where('user_id', '!=', $this->user->id)
                            ->where('torrent_id', '=', $this->torrent->id)
                            ->select(['id', 'peer_id'])
                            ->limit($this->numberOfWantedPeers)
                            ->inRandomOrder()
                            ->get();
        }

        $response['complete'] = $peers->where('left', '=', 0)->count();
        $response['incomplete'] = $peers->where('left', '!=', 0)->count();

        foreach ($peers as $peer) {
            foreach ($peer->IPs as $peerAddress) {
                $peerIPAddress = inet_pton($peerAddress->IP);
                $peerPort = pack("n*", $peerAddress->port);

                if (true === $peerAddress->isIPv6) {
                    $response['peers6'] .= $peerIPAddress . $peerPort;
                } else {
                    $response['peers'] .= $peerIPAddress . $peerPort;
                }
            }
        }

        //Storage::put('filename2.txt', print_r($peersArray, true));

        $response = $this->encoder->encode($response);

        Storage::put('filename2.txt', print_r($response, true));

        return $response;
    }

    protected function nonCompactResponse(): string
    {
        return $this->encoder->encode('5');
    }

    /**
     * @param array|string $error
     * @return string
     */
    protected function announceErrorResponse($error): string
    {
        $response['failure reason'] = '';
        if (is_array($error)) {
            foreach ($error as $message) {
                $response['failure reason'] .= $message . ' ';
            }
        } else {
            $response['failure reason'] = $error;
        }

        return $this->encoder->encode($response);
    }
}
