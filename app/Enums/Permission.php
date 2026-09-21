<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case CreateProposals = 'proposals.create';
    case ViewOwnProposals = 'proposals.view-own';
    case ViewAnyProposals = 'proposals.view-any';
    case ReviewProposals = 'proposals.review';
    case ChangeProposalStatus = 'proposals.change-status';
    case ViewReviews = 'reviews.view';
}
