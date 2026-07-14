<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', Rule::exists('sites', 'id')->where('is_active', true)],
            'title'   => 'nullable|string|max:255',
            'url'     => 'required|url|max:255',
            'anchor'  => 'required|string|max:255',
            'text'    => 'required|string',
            'type'    => 'required|in:post,homepage',
            'status'        => 'required|in:pending,published,failed',
            'failed_reason' => 'nullable|string',
            'check_status'  => 'required|in:unknown,alive,not_found,blocked,compromised',
            'check_error'   => 'nullable|string',
        ];
    }
}
