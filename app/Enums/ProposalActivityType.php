<?php

declare(strict_types=1);

namespace App\Enums;

enum ProposalActivityType: string
{
    case Submitted = 'proposal.submitted';
    case Reviewed = 'proposal.reviewed';
    case StatusChanged = 'proposal.status-changed';
}
