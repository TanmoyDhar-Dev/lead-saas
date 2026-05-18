<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'country'  => trim($this->country),
            'city'     => trim($this->city),
            'industry' => $this->industry ? trim($this->industry) : null,
            'position' => $this->position ? trim($this->position) : null,
            'volume'   => $this->volume ? (int) $this->volume : 10,
        ]);
    }

    public function rules(): array
    {
        return [
            'country'  => ['required', 'string', 'max:100'],
            'city'     => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:500'],
            'volume'   => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'volume.max' => 'Volume must be 100 or less. Please enter a valid number.',
            'volume.min' => 'Volume must be at least 1.',
        ];
    }
}
