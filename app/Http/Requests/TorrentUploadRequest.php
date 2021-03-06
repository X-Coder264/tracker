<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Http\FormRequest;

class TorrentUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'torrent'     => 'required|file|mimetypes:application/x-bittorrent',
            'name'        => 'required|string|min:5|max:255|unique:torrents',
            'description' => 'required|string|min:30',
            'category'    => 'required|exists:torrent_categories,id',
            'imdb_url'    => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        /** @var Translator $translator */
        $translator = $this->container->make(Translator::class);

        return [
            'torrent.mimetypes'    => $translator->get('messages.validation.torrent-upload-invalid-torrent-file'),
        ];
    }
}
