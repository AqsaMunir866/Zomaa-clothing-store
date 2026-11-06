<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:55'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => ['nullable', 'string', 'max:255', Password::min(8)->numbers()->letters()->symbols()],
        ];
    }

    /**
     * Friendly messages so the admin UI can show exactly what to fix.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => 'Please enter the user name.',
            'name.max' => 'The name may not be longer than 55 characters.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'A user with this email address already exists.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.max' => 'The password may not be longer than 255 characters.',
        ];
    }
}
