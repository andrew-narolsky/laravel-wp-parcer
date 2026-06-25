<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
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

        if (!$this->filled('password')) {
            $this->request->remove('password');
        }
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'url'      => 'required|url|max:255',
            'login'    => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
        ];
    }
}
