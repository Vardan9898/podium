<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Data\NotificationSelection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class MarkNotificationsReadRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'ids' => ['sometimes', 'array', 'max:100'],
            'ids.*' => ['uuid'],
        ];
    }

    public function toData(): NotificationSelection
    {
        /** @var list<string>|null $ids */
        $ids = $this->has('ids') ? array_values($this->array('ids')) : null;

        return new NotificationSelection($ids);
    }
}
