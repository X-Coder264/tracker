<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Locale;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Translation\Translator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(Guard $guard): bool
    {
        return $guard->check() && $guard->id() === $this->route('user')->id;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $localeIDs = Locale::select('id')->pluck('id');

        return [
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'locale_id' => [
                'required',
                Rule::in($localeIDs),
            ],
            'timezone' => 'required|timezone',
            'torrents_per_page' => 'required|integer|min:1|max:50',
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
            'email.required' => $translator->get('messages.validation.variable.required', ['var' => 'email']),
            'email.email' => $translator->get('messages.validation.variable.email'),
            'email.unique' => $translator->get('messages.validation.variable.email', ['var' => 'email']),
            'locale_id.required' => $translator->get('messages.validation.variable.required', ['var' => 'language']),
            'locale_id.in' => $translator->get('messages.validation.variable.invalid_value', ['var' => 'language']),
            'timezone.required' => $translator->get('messages.validation.variable.required', ['var' => 'timezone']),
            'timezone.timezone' => $translator->get('messages.validation.variable.invalid_value', ['var' => 'timezone']),
        ];
    }
}
