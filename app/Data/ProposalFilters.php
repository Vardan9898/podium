<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProposalStatus;

final readonly class ProposalFilters
{
    /**
     * @param  list<string>  $tags  Normalised tag keys (Tag::keyFor()); a proposal matches if it has any of them.
     */
    public function __construct(
        public ?string $search,
        public array $tags,
        public ?ProposalStatus $status,
        /** Reviewers only: limit to proposals they have not reviewed yet. */
        public bool $awaitingMyReview,
        public int $perPage,
    ) {}
}
