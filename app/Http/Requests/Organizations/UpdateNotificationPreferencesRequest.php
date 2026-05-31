<?php

namespace App\Http\Requests\Organizations;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.event_type' => ['required', Rule::enum(NotificationEventType::class)],
            'preferences.*.channel' => ['required', Rule::enum(NotificationChannel::class)],
            'preferences.*.is_enabled' => ['required', 'boolean'],
        ];
    }
}
