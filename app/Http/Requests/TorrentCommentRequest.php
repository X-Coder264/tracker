<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Http\FormRequest;

class TorrentCommentRequest extends FormRequest
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
            'comment' => 'required|string',
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
            'comment.required' => $translator->get('messages.validation.torrent-comment-required'),
        ];
    }
}
