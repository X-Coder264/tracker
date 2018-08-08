<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Translation\Translator;

class TorrentCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @param AuthManager $authManager
     *
     * @return bool
     */
    public function authorize(AuthManager $authManager): bool
    {
        return $authManager->guard()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
            'comment.required' => $translator->trans('messages.validation.torrent-comment-required'),
        ];
    }
}
