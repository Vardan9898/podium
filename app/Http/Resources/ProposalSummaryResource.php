<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\ProposalSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProposalSummary */
final class ProposalSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->total,
            /** @var array<string, int> */
            'by_status' => $this->byStatus,
            'awaiting_my_review' => $this->awaitingMyReview,
        ];
    }
}
