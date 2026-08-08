<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegrouperSignalementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signalement_ids' => ['required', 'array', 'min:1'],
            'signalement_ids.*' => ['integer', 'exists:signalements,id'],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,id'],
            'title' => ['required_without:incident_id', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ];
    }
}
