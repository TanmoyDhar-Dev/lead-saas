<?php

namespace App\Http\Requests;

use App\Models\ImportedOutreach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $parentId = $this->input('parent_outreach_id');
        if (! is_string($parentId) || $parentId === '') {
            return true; // let validation handle missing/invalid id
        }

        return ImportedOutreach::query()
            ->visibleTo($user)
            ->whereKey($parentId)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_outreach_id' => ['required', 'uuid', 'exists:imported_outreaches,id'],
            'selected_lead_ids' => ['required', 'array', 'min:1'],
            'selected_lead_ids.*' => ['required', 'uuid', 'distinct', 'exists:imported_leads,id'],
            'body_template' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_outreach_id.required' => 'A parent outreach campaign is required.',
            'parent_outreach_id.exists' => 'The selected parent outreach was not found.',
            'selected_lead_ids.required' => 'Select at least one lead to reply to.',
            'body_template.required' => 'A reply body is required.',
            'attachments.*.max' => 'Each attachment may not be greater than 5MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user === null) {
                return;
            }

            $leadIds = $this->input('selected_lead_ids', []);
            if (! is_array($leadIds) || $leadIds === []) {
                return;
            }

            $accessibleCount = \App\Models\ImportedLead::query()
                ->visibleTo($user)
                ->whereIn('id', $leadIds)
                ->count();

            if ($accessibleCount !== count(array_unique($leadIds))) {
                $validator->errors()->add(
                    'selected_lead_ids',
                    'One or more selected leads are not accessible.'
                );
            }
        });
    }
}
