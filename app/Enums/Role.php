<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Speaker = 'speaker';
    case Reviewer = 'reviewer';
    case Admin = 'admin';

    /**
     * The single source of truth for what each role is allowed to do.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Speaker => [
                Permission::CreateProposals,
                Permission::ViewOwnProposals,
            ],
            self::Reviewer => [
                Permission::ViewAnyProposals,
                Permission::ReviewProposals,
                Permission::ViewReviews,
            ],
            self::Admin => [
                Permission::ViewAnyProposals,
                Permission::ChangeProposalStatus,
                Permission::ViewReviews,
            ],
        };
    }
}
