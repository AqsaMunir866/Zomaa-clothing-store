<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $imageRule = ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'];

        if ($this->isMethod('post')) {
            $imageRule = ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'kicker' => ['nullable', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'image' => $imageRule,
            'link_label' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'position' => ['required', 'string', 'in:hero,split'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}