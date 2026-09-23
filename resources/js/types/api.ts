/**
 * Mirrors of the Laravel API Resources and backed enums. Keep in sync with app/Http/Resources and app/Enums.
 */

export const Permission = {
    CreateProposals: 'proposals.create',
    ViewOwnProposals: 'proposals.view-own',
    ViewAnyProposals: 'proposals.view-any',
    ReviewProposals: 'proposals.review',
    ChangeProposalStatus: 'proposals.change-status',
    ViewReviews: 'reviews.view',
} as const;
export type Permission = (typeof Permission)[keyof typeof Permission];

export const Role = {
    Speaker: 'speaker',
    Reviewer: 'reviewer',
    Admin: 'admin',
} as const;
export type Role = (typeof Role)[keyof typeof Role];

export const ProposalStatus = {
    Pending: 'pending',
    Approved: 'approved',
    Rejected: 'rejected',
} as const;
export type ProposalStatus = (typeof ProposalStatus)[keyof typeof ProposalStatus];

export const ActivityType = {
    Submitted: 'proposal.submitted',
    Reviewed: 'proposal.reviewed',
    StatusChanged: 'proposal.status-changed',
} as const;
export type ActivityType = (typeof ActivityType)[keyof typeof ActivityType];

export interface User {
    id: number;
    name: string;
}

export interface CurrentUser extends User {
    email: string;
    role: Role | null;
    permissions: Permission[];
}

export interface Tag {
    id: number;
    name: string;
}

export interface Review {
    id: number;
    rating: number;
    comment: string;
    reviewer?: User;
    created_at: string;
    updated_at: string;
}

export interface Proposal {
    id: number;
    title: string;
    description: string;
    status: ProposalStatus;
    author?: User;
    tags?: Tag[];
    attachment: { name: string; url: string } | null;
    reviews_count?: number;
    average_rating?: number | null;
    reviews?: Review[];
    created_at: string;
    updated_at: string;
}

export interface ProposalSummary {
    total: number;
    by_status: Record<ProposalStatus, number>;
    awaiting_my_review: number | null;
}

export interface ActivityPayload {
    type: ActivityType;
    proposal_id: number;
    proposal_title: string;
    message: string;
    actor_name: string;
}

export interface AppNotification {
    id: string;
    type: ActivityType;
    data: ActivityPayload;
    read_at: string | null;
    created_at: string;
}

export interface ClientConfig {
    registerable_roles: Role[];
    rating: { min: number; max: number };
    attachment_max_kilobytes: number;
    tags_max_per_proposal: number;
}

export interface Resource<T> {
    data: T;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
}

export interface ValidationErrorBody {
    message: string;
    errors: Record<string, string[]>;
}
