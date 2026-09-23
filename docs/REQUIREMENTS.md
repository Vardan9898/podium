# Requirements coverage

Every line of the challenge brief, where it is implemented, and the test that proves it.
Test names are the `it(...)` descriptions — run one with
`./vendor/bin/sail artisan test --filter="<part of the name>"`.

## Authentication & Authorization

| Requirement | Implementation | Proof |
|---|---|---|
| User registration and login | `Auth\RegisterController`, `Auth\SessionController`, `Actions\Auth\RegisterUser`. Sanctum SPA cookie sessions — no tokens in JavaScript. | *registers a user with the chosen role and signs them in*, *logs in with valid credentials*, *logs out and invalidates the session* |
| Three roles: speaker, reviewer, admin | `App\Enums\Role`; permissions per role live in `Role::permissions()` and are synced by `RolesAndPermissionsSeeder`. | *creates a user with a hashed password and exactly one role* |
| Role chosen during registration | `RegisterRequest` validates `role` against `SELF_REGISTRATION_ROLES` (`.env.example` ships all three, as the brief asks). | *only allows the roles configured as self-registerable*, *rejects every role when the configured list is empty* |
| Role-based access control | `ProposalPolicy` + route `can` middleware. Policies check **permissions**, never role names. | *enforces access rules* (14 endpoints × 4 kinds of caller = 56 cases), *covers every API route* |

## Talk proposal submission

| Requirement | Implementation | Proof |
|---|---|---|
| Speakers see **their own** proposals | `Proposal::scopeVisibleTo()`, mirrored by `ProposalPolicy::view()` | *shows speakers only their own proposals*, *keeps the list scope and the view policy in agreement* |
| Speakers submit proposals | `Actions\Proposals\SubmitProposal` (one transaction: row, file, tags, event) | *submits a proposal as pending without tags or file* |
| Title required, description required | `StoreProposalRequest` | *rejects invalid submissions* (dataset covers missing title, missing description, over-long title) |
| Tags optional | `tags` nullable, max 10 | *creates new tags and reuses existing ones case-insensitively* |
| Attached file optional | `attachment` nullable | *submits a proposal as pending without tags or file* |
| PDF only, max 4 MB | Extension **and** sniffed MIME type, size from `config/proposals.php` | *rejects invalid submissions* (non-PDF, image renamed `.pdf`, > 4 MB) |
| Tags created dynamically or picked from existing | One `TagInput` field: autocomplete from `/api/tags`, unknown names created on submit by `Actions\Tags\SyncProposalTags` | *creates new tags and reuses existing ones case-insensitively*, Vitest *offers matching existing tags and picks one with the keyboard* |
| Status `pending` / `approved` / `rejected`, default `pending` | `App\Enums\ProposalStatus`, column default | *submits a proposal as pending without tags or file* |

## Review system

| Requirement | Implementation | Proof |
|---|---|---|
| Reviewers see all proposals | `proposals.view-any` permission | *shows reviewers and admins every proposal* |
| Rating 1–10 + comment | `ReviewProposalRequest` reads the range from `config/proposals.php`; one review per reviewer per proposal, re-submitting updates it | *creates a review, then updates the same one on resubmit*, *enforces rating bounds from config*, *keeps one review per reviewer while allowing many reviewers* |
| Admins change status | `Actions\Proposals\ChangeProposalStatus` (row-locked, no-op when unchanged) | *changes the status and dispatches an event*, *does nothing and dispatches no event when the status is unchanged*, *forbids reviewers, who may see the proposal but not decide* |
| List filtered by tags | `scopeWithAnyTags()`, matched by name, case-insensitive | *filters by any of several tags, matched by name case-insensitively* |
| List searched by title | `scopeTitleMatches()` through Laravel Scout | *searches by title, case-insensitively and treating wildcards literally* |

## Technical requirements

| Requirement | Implementation | Proof |
|---|---|---|
| Backend: Laravel API | Laravel 12, JSON only, everything under `/api`; the SPA gets no Blade-rendered data | `php artisan route:list` |
| Frontend: Vue.js | Vue 3 `<script setup lang="ts">`, Vue Router, Pinia, Tailwind | `npm run build` |
| Responsive design | Measured at 320/360/375/414/768 px: no horizontal scrolling, list becomes cards, tap targets ≥ 24 px | manual pass recorded in the README |
| Data validation | FormRequests only; field-level 422 errors shown per input | *validates registration input*, *rejects invalid submissions*, *validates the review payload* |
| Error handling | `401/403/404/419/429` are JSON `{message}`; SPA has loading, empty and error states everywhere; 419 refreshes CSRF and replays once | *rejects anonymous access with a JSON 401*, *returns a generic JSON 404 for unknown proposals*, Vitest *refreshes the CSRF cookie once and replays the request after a 419* |

## Bonus features

| Bonus | Implementation | Proof |
|---|---|---|
| Real-time updates (Laravel Broadcasting + Vue) | Laravel Reverb; domain events → queued listeners → one notification on `database` + `broadcast`; SPA toasts, bell and live refresh | *notifies everyone who can see all proposals when one is submitted*, *authorises a user for their own private channel only*, plus a manual end-to-end check inside Docker |
| Advanced search (Laravel Scout) | Scout with the `database` engine by default, swappable to Meilisearch via `SCOUT_DRIVER` | *finds proposals by title with any Scout engine*, and the `meilisearch` group against a real engine in its own CI job |
| Tests (unit or integration, front or back) | 208 Pest + 62 Vitest | `composer check`, `npm test` |
| API documentation (Swagger/OpenAPI) | Scramble generates OpenAPI 3.1 from FormRequests and Resources at `/docs/api` | open `/docs/api` |

## Deliverables

| Deliverable | Where |
|---|---|
| Complete source with clear folder structure and documentation | This repository; structure in [README → Architecture](../README.md#architecture) |
| Build and deployment instructions | [README → Quick start](../README.md#quick-start-sail), [Running without Docker](../README.md#running-without-docker), [Deploying](../README.md#deploying) |
| Database migrations and seeders with sample data | `database/migrations`, `database/seeders` — 3 demo users, 10 tags, 25 realistic talks with reviews and a sample PDF |

## Deliberately not built

Called out so a reviewer does not go looking for them:

- Editing or withdrawing a proposal, and reviewer assignment — not in the brief.
- Status workflow rules: an admin may move a proposal back to `pending`.
- Pruning notification history (a scheduled job in a real system).
- Password reset and email verification — no mail requirement in the brief.
