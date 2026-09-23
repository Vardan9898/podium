<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Speaker = 'speaker';
    case Reviewer = 'reviewer';
    case Admin = 'admin';

    /**
     * Roles a visitor may pick for themselves at sign-up (see config/auth.php).
     *
     * @return list<self>
     */
    public static function selfRegisterable(): array
    {
        /** @var list<string> $allowed */
        $allowed = config()->array('auth.self_registration_roles');

        return array_values(array_filter(self::cases(), fn (self $role): bool => in_array($role->value, $allowed, true)));
    }

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
