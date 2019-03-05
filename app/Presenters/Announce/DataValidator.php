<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use App\Enumerations\AnnounceEvent;
use Illuminate\Translation\Translator;
use App\Exceptions\AnnounceValidationException;
use Illuminate\Validation\Factory as ValidationFactory;

class DataValidator
{
    private $validationFactory;

    private $translator;

    public function __construct(
        ValidationFactory $validationFactory,
        Translator $translator
    ) {
        $this->validationFactory = $validationFactory;
        $this->translator = $translator;
    }

    /**
     * @throws AnnounceValidationException
     */
    public function validate(array $data): void
    {
        $event = AnnounceEvent::PING;
        if (isset($data['event'])) {
            $event = $data['event'];
        }

        $this->validateEvent($event);
        $this->validateInfoHash($data['info_hash']);
        $this->validatePeerID($data['peer_id']);
        $this->validateData($data);
    }

    /**
     * @throws AnnounceValidationException
     */
    protected function validateEvent(?string $event): void
    {
        if (AnnounceEvent::PING === $event) {
            return;
        }

        if (!in_array($event, [AnnounceEvent::STARTED, AnnounceEvent::STOPPED, AnnounceEvent::COMPLETED])) {
            $errorMessage = $this->translator->trans('messages.validation.variable.event');

            throw new AnnounceValidationException($errorMessage);
        }
    }

    /**
     * @throws AnnounceValidationException
     */
    protected function validateInfoHash(?string $infoHash): void
    {
        if (null === $infoHash) {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (20 !== strlen($infoHash)) {
            $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }
    }

    private function validatePeerID(?string $peerId): void
    {
        if (null === $peerId) {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (20 !== strlen($peerId)) {
            $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }
    }

    /**
     * @throws AnnounceValidationException
     */
    private function validateData(array $data): void
    {
        $validator = $this->validationFactory->make(
            $data,
            [
                'passkey'    => 'required|string|size:64',
                'port'       => 'required|integer|min:1|max:65535',
                'uploaded'   => 'required|integer|min:0',
                'downloaded' => 'required|integer|min:0',
                'left'       => 'required|integer|min:0',
                'numwant'    => 'sometimes|integer',
            ],
            [
                'passkey.required'    => $this->translator->trans('messages.validation.variable.required', ['var' => 'passkey']),
                'passkey.string'      => $this->translator->trans('messages.validation.variable.string', ['var' => 'passkey']),
                'passkey.size'        => $this->translator->trans('messages.validation.variable.size', ['var' => 'passkey']),
                'port.required'       => $this->translator->trans('messages.validation.variable.required', ['var' => 'port']),
                'port.integer'        => $this->translator->trans('messages.validation.variable.port', ['port' => $data['port']]),
                'port.min'            => $this->translator->trans('messages.validation.variable.port', ['port' => $data['port']]),
                'port.max'            => $this->translator->trans('messages.validation.variable.port', ['port' => $data['port']]),
                'uploaded.required'   => $this->translator->trans('messages.validation.variable.required', ['var' => 'uploaded']),
                'uploaded.integer'    => $this->translator->trans('messages.validation.variable.integer', ['var' => 'uploaded']),
                'uploaded.min'        => $this->translator->trans('messages.validation.variable.uploaded', ['uploaded' => $data['uploaded']]),
                'downloaded.required' => $this->translator->trans('messages.validation.variable.required', ['var' => 'downloaded']),
                'downloaded.integer'  => $this->translator->trans('messages.validation.variable.integer', ['var' => 'downloaded']),
                'downloaded.min'      => $this->translator->trans('messages.validation.variable.downloaded', ['downloaded' => $data['downloaded']]),
                'left.required'       => $this->translator->trans('messages.validation.variable.required', ['var' => 'left']),
                'left.integer'        => $this->translator->trans('messages.validation.variable.integer', ['var' => 'left']),
                'left.min'            => $this->translator->trans('messages.validation.variable.left', ['left' => $data['left']]),
                'numwant.integer'     => $this->translator->trans('messages.validation.variable.integer', ['var' => 'numwant']),
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            throw new AnnounceValidationException('', $errors->all());
        }
    }
}
