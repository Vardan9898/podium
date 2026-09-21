<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProposalActivityType;
use App\Models\Proposal;
use App\Models\User;

/**
 * Notification payload. Holds only primitives so queued notifications
 * never re-query the database and never leak more than they should.
 */
final readonly class ProposalActivity
{
    public function __construct(
        public ProposalActivityType $type,
        public int $proposalId,
        public string $proposalTitle,
        public string $message,
        public string $actorName,
    ) {}

    public static function for(ProposalActivityType $type, Proposal $proposal, User $actor, string $message): self
    {
        return new self($type, $proposal->id, $proposal->title, $message, $actor->name);
    }

    /**
     * @return array{type: string, proposal_id: int, proposal_title: string, message: string, actor_name: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'proposal_id' => $this->proposalId,
            'proposal_title' => $this->proposalTitle,
            'message' => $this->message,
            'actor_name' => $this->actorName,
        ];
    }
}
