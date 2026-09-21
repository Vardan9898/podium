<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Server settings the SPA needs for UX (limits, options). The API still enforces all of them.
 */
final class ClientConfigResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'allow_admin_registration' => config()->boolean('auth.allow_admin_registration'),
            'rating' => [
                'min' => config()->integer('proposals.rating.min'),
                'max' => config()->integer('proposals.rating.max'),
            ],
            'attachment_max_kilobytes' => config()->integer('proposals.attachment.max_kilobytes'),
            'tags_max_per_proposal' => config()->integer('proposals.tags.max_per_proposal'),
        ];
    }
}
