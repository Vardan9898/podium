<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

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

    /**
     * @return list<string>|null Null means "all unread".
     */
    public function ids(): ?array
    {
        /** @var list<string>|null $ids */
        $ids = $this->has('ids') ? array_values($this->array('ids')) : null;

        return $ids;
    }
}
