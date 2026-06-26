<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'title'   => 'required|string|max:255',
            'url'     => 'required|url|max:255',
            'anchor'  => 'required|string|max:255',
            'text'    => 'required|string',
            'type'    => 'required|in:post,homepage',
        ];
    }
}
