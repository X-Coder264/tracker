<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use App\Exceptions\AnnounceValidationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class AnnounceRequest
{
    private string $infoHash;
    private string $peerId;
    private AnnounceEvent $event;
    private string $passkey;
    private IpAddress $clientIp;
    private int $port;
    private int $uploaded;
    private int $downloaded;
    private int $left;
    private int $numberOfWantedPeers;
    private bool $expectsCompactResponse;
    private string $userAgent;
    private ?string $key;

    private function __construct(
        string $infoHash,
        string $peerId,
        AnnounceEvent $event,
        string $passkey,
        IpAddress $clientIp,
        int $port,
        int $uploaded,
        int $downloaded,
        int $left,
        int $numberOfWantedPeers,
        bool $expectsCompactResponse,
        string $userAgent,
        ?string $key
    ) {
        $this->infoHash = $infoHash;
        $this->peerId = $peerId;
        $this->event = $event;
        $this->passkey = $passkey;
        $this->clientIp = $clientIp;
        $this->port = $port;
        $this->uploaded = $uploaded;
        $this->downloaded = $downloaded;
        $this->left = $left;
        $this->numberOfWantedPeers = $numberOfWantedPeers;
        $this->expectsCompactResponse = $expectsCompactResponse;
        $this->userAgent = $userAgent;
        $this->key = $key;
    }

    /**
     * @throws AnnounceValidationException
     */
    public static function fromHttpRequest(Request $request, ValidationFactory $validationFactory, Translator $translator): self
    {
        if (true !== $request->filled('info_hash')) {
            $errorMessage = $translator->get('messages.validation.variable.required', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }

        // info_hash and peer_id are validated separately because the Laravel validator uses
        // mb_strlen to get the length of the (sometimes binary) string which returns a wrong number
        // when used on those two properties so strlen must be used
        // mb_strlen returns a "wrong" number because it counts code points (while strlen counts bytes)
        if (20 !== strlen($request->query('info_hash'))) {
            $errorMessage = $translator->get('messages.validation.variable.size', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (true !== $request->filled('peer_id')) {
            $errorMessage = $translator->get('messages.validation.variable.required', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (20 !== strlen($request->query('peer_id'))) {
            $errorMessage = $translator->get('messages.validation.variable.size', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }

        $validator = $validationFactory->make(
            $request->query(),
            [
                'passkey'    => 'required|string|size:64',
                'port'       => 'required|integer|min:1|max:65535',
                'uploaded'   => 'required|integer|min:0',
                'downloaded' => 'required|integer|min:0',
                'left'       => 'required|integer|min:0',
                'numwant'    => 'sometimes|integer|min:1',
            ],
            [
                'passkey.required'    => $translator->get('messages.validation.variable.required', ['var' => 'passkey']),
                'passkey.string'      => $translator->get('messages.validation.variable.string', ['var' => 'passkey']),
                'passkey.size'        => $translator->get('messages.validation.variable.size', ['var' => 'passkey']),
                'port.required'       => $translator->get('messages.validation.variable.required', ['var' => 'port']),
                'port.integer'        => $translator->get('messages.validation.variable.port', ['port' => $request->query('port')]),
                'port.min'            => $translator->get('messages.validation.variable.port', ['port' => $request->query('port')]),
                'port.max'            => $translator->get('messages.validation.variable.port', ['port' => $request->query('port')]),
                'uploaded.required'   => $translator->get('messages.validation.variable.required', ['var' => 'uploaded']),
                'uploaded.integer'    => $translator->get('messages.validation.variable.integer', ['var' => 'uploaded']),
                'uploaded.min'        => $translator->get('messages.validation.variable.uploaded', ['uploaded' => $request->query('uploaded')]),
                'downloaded.required' => $translator->get('messages.validation.variable.required', ['var' => 'downloaded']),
                'downloaded.integer'  => $translator->get('messages.validation.variable.integer', ['var' => 'downloaded']),
                'downloaded.min'      => $translator->get('messages.validation.variable.downloaded', ['downloaded' => $request->query('downloaded')]),
                'left.required'       => $translator->get('messages.validation.variable.required', ['var' => 'left']),
                'left.integer'        => $translator->get('messages.validation.variable.integer', ['var' => 'left']),
                'left.min'            => $translator->get('messages.validation.variable.left', ['left' => $request->query('left')]),
                'numwant.integer'     => $translator->get('messages.validation.variable.integer', ['var' => 'numwant']),
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            throw new AnnounceValidationException('', $errors->all());
        }

        // the default is compact response, unless the client explicitly wants the non compact response
        $expectsCompactResponse = true;
        if ($request->has('compact') && '0' === $request->query('compact')) {
            $expectsCompactResponse = false;
        }

        $userAgent = $request->userAgent();

        if (empty($userAgent)) {
            throw new AnnounceValidationException('User agent is required.');
        }

        $clientIp = $request->getClientIp();

        if (empty($clientIp)) {
            throw new AnnounceValidationException('Client IP is required.');
        }

        try {
            $ip = new IpAddress($clientIp);
        } catch (InvalidArgumentException $e) {
            throw new AnnounceValidationException($e->getMessage(), [], 0, $e);
        }

        return new self(
            $request->query('info_hash'),
            $request->query('peer_id'),
            new AnnounceEvent($request->query('event')),
            $request->query('passkey'),
            $ip,
            (int) $request->query('port'),
            (int) $request->query('uploaded'),
            (int) $request->query('downloaded'),
            (int) $request->query('left'),
            (int) $request->query('numwant', 50),
            $expectsCompactResponse,
            $userAgent,
            $request->query('key')
        );
    }

    public function getInfoHash(): string
    {
        return $this->infoHash;
    }

    public function getPeerId(): string
    {
        return $this->peerId;
    }

    public function getEvent(): AnnounceEvent
    {
        return $this->event;
    }

    public function getPasskey(): string
    {
        return $this->passkey;
    }

    public function getClientIp(): IpAddress
    {
        return $this->clientIp;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUploaded(): int
    {
        return $this->uploaded;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getNumberOfWantedPeers(): int
    {
        return $this->numberOfWantedPeers;
    }

    public function isCompactResponseExpected(): bool
    {
        return $this->expectsCompactResponse;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
