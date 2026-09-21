<?php

declare(strict_types=1);

/*
 * Demo content for DatabaseSeeder: [title, abstract, tags].
 */
return [
    ['Zero-downtime deploys with Envoyer and Horizon', "How we ship a Laravel monolith to production 40 times a day without dropping a queued job.\n\nWe'll cover atomic symlink releases, draining Horizon supervisors, and making jobs safe to run on two code versions at once.", ['Laravel', 'DevOps']],
    ['Eloquent performance: finding the N+1 you didn\'t know you had', "Strict mode, query logging and a handful of habits that keep response times flat as tables grow.\n\nIncludes a live profiling session on a real-world slow endpoint.", ['Laravel', 'Performance']],
    ['Designing permission systems that survive the org chart', "Roles change every quarter; permissions shouldn't. A practical model for authorization that keeps policies boring and auditable.", ['Security', 'Architecture']],
    ['Vue 3 composables beyond the basics', 'Patterns for composables that own async state, cancellation and cleanup — and the anti-patterns that make them leak.', ['Vue.js']],
    ['Testing the untestable: legacy PHP under a safety net', 'Characterisation tests, seams and Pest datasets to put a 10-year-old codebase under test before refactoring it.', ['PHP', 'Testing']],
    ['Real-time without the pain: Laravel Reverb in production', 'Scaling WebSockets horizontally with Redis, authorising private channels, and what to monitor once real users connect.', ['Laravel', 'Performance']],
    ['Accessible forms are better forms', 'Labels, error announcements, focus management and keyboard support — with before/after screen reader recordings.', ['Accessibility', 'Vue.js']],
    ['From junior to senior: the parts nobody writes down', 'Code review etiquette, estimating, saying no, and owning an outage. Lessons from mentoring 30 engineers.', ['Career']],
    ['Postgres features every Laravel developer should know', 'Partial indexes, generated columns, JSONB and CREATE INDEX CONCURRENTLY — from migrations, safely.', ['PHP', 'Performance']],
    ['Threat modelling a SaaS app in one afternoon', 'A lightweight STRIDE walkthrough on a real multi-tenant Laravel app, ending with a prioritised backlog.', ['Security']],
    ['Hexagonal architecture without the ceremony', 'Keeping domain logic framework-agnostic where it pays off, and embracing the framework everywhere else.', ['Architecture', 'PHP']],
    ['Static analysis at level max', 'Getting Larastan to level 9 on a large codebase incrementally with baselines, generics and custom rules.', ['PHP', 'Testing']],
    ['Building a design system with Tailwind v4', 'Tokens, theming and component APIs that designers and developers both enjoy using.', ['Vue.js', 'Accessibility']],
    ['Queues, retries and idempotency', 'Why every job must be safe to run twice, and how to get there with unique jobs, locks and outbox tables.', ['Laravel', 'Architecture']],
    ['Observability for small teams', "Structured logs, a few golden-signal dashboards and alerts that don't wake you up for nothing.", ['DevOps']],
    ['Type-safe APIs from Laravel to TypeScript', 'Generating OpenAPI with Scramble and deriving TypeScript clients so the frontend never guesses a payload again.', ['Laravel', 'Vue.js']],
    ['Feature flags as a deployment strategy', 'Trunk-based development with Pennant: dark launches, gradual rollouts and cleaning up flags before they rot.', ['Laravel', 'DevOps']],
    ['What we learned rewriting our search', 'Moving from LIKE queries to a dedicated engine, measuring relevance, and the migration that almost went wrong.', ['Performance', 'Architecture']],
    ['Mutation testing: are your tests actually testing?', 'Using Infection and Pest to find tests that pass no matter what the code does.', ['Testing', 'PHP']],
    ['Secure file uploads in PHP', 'MIME sniffing, private disks, signed URLs and why the file extension is a lie.', ['Security', 'PHP']],
    ['The art of the pull request', 'Small diffs, great descriptions and reviews that teach instead of gatekeep.', ['Career']],
    ['Keyboard-first interfaces', 'Designing complex widgets — comboboxes, menus, data grids — that power users can drive without a mouse.', ['Accessibility']],
    ['Scaling a Laravel app to a million users on a budget', 'Caching layers, read replicas and the boring infrastructure choices that got us there.', ['Laravel', 'Performance', 'DevOps']],
    ['Domain events in practice', 'Using events to decouple side effects without turning the codebase into a game of spaghetti telephone.', ['Architecture', 'Laravel']],
    ['Pest 3 for PHPUnit veterans', 'Datasets, architecture tests and the migration path for a 5,000-test suite.', ['Testing', 'PHP']],
];
