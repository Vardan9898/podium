<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProposalStatus;

/**
 * Counts for the dashboard strip, already scoped to what the viewer may see.
 */
final readonly class ProposalSummary
{
    /**
     * @param  array<string, int>  $byStatus  Every status, including the ones at zero.
     * @param  int|null  $awaitingMyReview  Null for users who do not review.
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public ?int $awaitingMyReview,
    ) {}

    /**
     * @param  array<string, int>  $counts
     */
    public static function fromCounts(array $counts, ?int $awaitingMyReview): self
    {
        $byStatus = [];

        foreach (ProposalStatus::cases() as $status) {
            $byStatus[$status->value] = $counts[$status->value] ?? 0;
        }

        return new self(array_sum($byStatus), $byStatus, $awaitingMyReview);
    }
}
