<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyticsEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:25'],
            'events.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'events.*.ts' => ['required', 'integer', 'min:1000000000', 'max:9999999999'], // Unix timestamp
            'events.*.path' => ['nullable', 'string', 'max:500'],
            'events.*.referrer' => ['nullable', 'string', 'max:500'],
            'events.*.utm.source' => ['nullable', 'string', 'max:100'],
            'events.*.utm.medium' => ['nullable', 'string', 'max:100'],
            'events.*.utm.campaign' => ['nullable', 'string', 'max:100'],
            'events.*.utm.term' => ['nullable', 'string', 'max:100'],
            'events.*.utm.content' => ['nullable', 'string', 'max:100'],
            'events.*.session_id' => ['nullable', 'string', 'max:100'],
            'events.*.anonymous_id' => ['nullable', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'events.max' => 'Maximum 25 events allowed per request',
            'events.*.name.regex' => 'Event name must contain only lowercase letters, numbers, and underscores',
            'events.*.ts.min' => 'Timestamp must be a valid Unix timestamp',
            'events.*.ts.max' => 'Timestamp must be a valid Unix timestamp',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('events')) {
            $events = $this->input('events');

            // Sanitize each event
            $sanitized = array_map(function ($event) {
                // Trim strings
                if (isset($event['name'])) {
                    $event['name'] = trim($event['name']);
                }
                if (isset($event['path'])) {
                    $event['path'] = trim($event['path']);
                }
                if (isset($event['referrer'])) {
                    $event['referrer'] = trim($event['referrer']);
                }

                // Limit properties JSON size (8KB)
                if (isset($event['properties'])) {
                    $propertiesJson = json_encode($event['properties']);
                    if (strlen($propertiesJson) > 8192) {
                        $event['properties'] = ['_truncated' => true];
                    }
                }

                return $event;
            }, $events);

            $this->merge(['events' => $sanitized]);
        }
    }
}
