<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProposalStatus;

final readonly class ProposalFilters
{
    /**
     * @param  list<string>  $tags  Tag slugs; a proposal matches if it has any of them.
     */
    public function __construct(
        public ?string $search,
        public array $tags,
        public ?ProposalStatus $status,
        public int $perPage,
    ) {}
}
