<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Locale;
use Illuminate\Validation\Rule;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Translation\Translator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(AuthManager $authManager): bool
    {
        return $authManager->guard()->check() && $authManager->guard()->id() === $this->route('user')->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
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
            'email.required' => $translator->trans('messages.validation.variable.required', ['var' => 'email']),
            'email.email' => $translator->trans('messages.validation.variable.email'),
            'email.unique' => $translator->trans('messages.validation.variable.email', ['var' => 'email']),
            'locale_id.required' => $translator->trans('messages.validation.variable.required', ['var' => 'language']),
            'locale_id.in' => $translator->trans('messages.validation.variable.invalid_value', ['var' => 'language']),
            'timezone.required' => $translator->trans('messages.validation.variable.required', ['var' => 'timezone']),
            'timezone.timezone' => $translator->trans('messages.validation.variable.invalid_value', ['var' => 'timezone']),
        ];
    }
}
