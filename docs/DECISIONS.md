# Decisions

Short notes on the choices a reviewer is most likely to question, and what each one cost.

## 1. Permissions, not roles, everywhere but the seeder

`ProposalPolicy` and the SPA both ask "may this user review?", never "is this user a reviewer?".
Roles exist only as bundles of permissions, defined once in `App\Enums\Role::permissions()` and
synced by the seeder.

*Why:* role checks scattered through a codebase are what makes authorization impossible to change
later. Adding a "chair" role here is one enum case plus a seeder run — no `if` statements move.

*Cost:* one extra indirection when reading the code, and a permission table to seed.

## 2. Thin controllers → Actions → DTOs

Each controller resolves input, calls one Action and returns a Resource. Actions are `final`
classes with a single `handle()`, take `readonly` DTOs rather than arrays, own their transactions
and dispatch events. They never see a `Request` and never build a response.

*Why:* the business rules stay callable from anywhere (a console command, a queued job, a test)
and are testable without HTTP. It also keeps the controllers honest: there is nowhere to hide a
query.

*Cost:* more small classes. Worth it at this size; I would not add a repository layer on top —
Eloquent is already the data layer.

## 3. Invisible proposals answer 404, not 403

A speaker asking for someone else's proposal gets exactly the same response as for an id that
does not exist, on every endpoint, with an identical body.

*Why:* 403 confirms the record exists, which turns any list endpoint into an id oracle.

*Cost:* the API cannot distinguish "not yours" from "gone" in its own messages. Acceptable — the
UI never needs that distinction.

## 4. Search through Scout, but inlined on the default engine

`SCOUT_DRIVER=database` (the default) inlines the title match into the same SQL as the visibility,
tag and status filters. A hosted engine can only return ids, so that path resolves ids first
(capped) and filters them in SQL.

*Why:* an earlier version asked the engine for ids in both cases. The cap was then applied
*before* visibility, so a speaker could search for their own talk and get nothing, and totals were
silently wrong. Splitting the two paths makes the default exact at any size and keeps the
Meilisearch swap a one-line env change.

*Cost:* two code paths, both tested — the second against a real Meilisearch in CI.

## 5. Notifications can never break a write

Domain events implement `ShouldDispatchAfterCommit`, listeners are queued, and the notification
itself is queued. Payloads carry primitives only.

*Why:* a broker outage should never roll back a proposal or delete its attachment, and a queued
job should not re-query models that may have changed. Listeners also decide recipients *by
permission*, so a new role that can see all proposals starts receiving notifications with no
listener change.

*Cost:* delivery is eventual, and in development the queue worker must be running (the Sail setup
starts one). Notification content is fixed at dispatch time, which is why the status event carries
both the previous and the new status.

## 6. Speakers never see review data

Ratings, counts and averages are stripped from the API response for anyone without
`reviews.view` — not merely hidden in the UI — and the "your proposal was reviewed" notice names
no reviewer.

*Why:* reviewer candour depends on it, and hiding data only in the client is not hiding it.

*Cost:* speakers get less feedback than they might like. A deliberate product choice, easy to
reverse.

## 7. `/api/config` instead of duplicated constants

Rating range, upload limit, tag limit and which roles may self-register come from one public
endpoint.

*Why:* the alternative is the same numbers written twice, drifting quietly. Changing
`config/proposals.php` now changes the form, its counters and its validation together.

*Cost:* one request on first load, with the server defaults mirrored in the store as a fallback.

## 8. Self-registration is a configurable list

The brief asks for all three roles at sign-up. `SELF_REGISTRATION_ROLES` ships with all three and
falls back to speaker-only when unset.

*Why:* letting anyone become a reviewer or admin is a privilege-escalation vector; the brief's
requirement should not force it onto a real deployment.

*Cost:* one more setting. Note the subtlety it caused: `Rule::enum()->only([])` means *no
restriction*, so the rule uses `Rule::in()` — with an empty list that rejects everything.

## 9. Tag identity is a case-folded name, not a slug

`tags.normalized_name` is the trimmed, whitespace-collapsed, lower-cased name, with a unique index.

*Why:* slugging looked right until `C`, `C#` and `C++` all became `c` and a Japanese tag became an
empty string. Case folding keeps the distinctions users care about.

*Cost:* `Café` and `Cafe` remain separate tags; folding accents properly needs ICU collation.

## 10. One shared list, not a screen per role

Every role lands on `/proposals`. The dashboard counts, the columns and the controls change with
the viewer's permissions.

*Why:* three near-identical screens drift apart. One screen with permission-driven parts keeps the
behaviour in one place and matches how the API already scopes data.

*Cost:* the list component has to be honest about optional fields (a speaker's payload has no
rating data at all), which the TypeScript types make explicit.
