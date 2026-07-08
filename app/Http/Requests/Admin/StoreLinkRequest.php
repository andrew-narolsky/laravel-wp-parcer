<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', Rule::exists('sites', 'id')->where('is_active', true)],
            'title'   => 'required|string|max:255',
            'url'     => 'required|url|max:255',
            'anchor'  => 'required|string|max:255',
            'text'    => 'required|string',
            'type'    => 'required|in:post,homepage',
        ];
    }
}
