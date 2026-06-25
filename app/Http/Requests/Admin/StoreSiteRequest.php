<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('url')) {
            $this->merge(['url' => rtrim($this->url, '/')]);
        }
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'url'      => 'required|url|max:255',
            'login'    => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ];
    }
}
