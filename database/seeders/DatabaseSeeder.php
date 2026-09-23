<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $speaker = $this->demoUser('Sam Speaker', 'speaker@example.com', Role::Speaker);
        $reviewer = $this->demoUser('Riley Reviewer', 'reviewer@example.com', Role::Reviewer);
        $this->demoUser('Alex Admin', 'admin@example.com', Role::Admin);

        $speakers = User::factory()->count(3)->speaker()->create()->prepend($speaker);
        $reviewers = User::factory()->count(2)->reviewer()->create()->prepend($reviewer);

        if (Proposal::query()->exists()) {
            $this->command->info('Demo proposals already present — roles and demo users refreshed, nothing else added.');

            return;
        }

        /** @var list<array{0: string, 1: string, 2: list<string>}> $talks */
        $talks = require __DIR__.'/data/talks.php';
        /** @var list<string> $comments */
        $comments = require __DIR__.'/data/review-comments.php';

        $tags = collect($talks)->pluck(2)->flatten()->unique()
            ->mapWithKeys(fn (string $name): array => [$name => Tag::query()->firstOrCreate(
                ['normalized_name' => Tag::keyFor($name)],
                ['name' => Tag::normalizeName($name)],
            )]);

        $statuses = ProposalStatus::cases();

        foreach ($talks as $i => [$title, $abstract, $tagNames]) {
            $proposal = Proposal::factory()
                ->for($i % 4 === 0 ? $speaker : $speakers->random(), 'author')
                ->status($statuses[$i % count($statuses)])
                ->withTags($tags->only($tagNames))
                ->create([
                    'title' => $title,
                    'description' => $abstract,
                    'created_at' => now()->subHours(($i + 1) * 7),
                ]);

            foreach ($reviewers->random(random_int(0, $reviewers->count())) as $author) {
                Review::factory()->for($proposal)->for($author, 'author')->create([
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
        }

        $this->attachSamplePdf($speaker);
    }

    /** Idempotent: `db:seed` on an already-seeded database updates instead of colliding. */
    private function demoUser(string $name, string $email, Role $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user = User::factory()->create(['name' => $name, 'email' => $email]);
        }

        $user->syncRoles([$role]);

        return $user;
    }

    private function attachSamplePdf(User $speaker): void
    {
        $proposal = $speaker->proposals()->latest()->firstOrFail();
        $path = "proposals/{$proposal->id}/sample-proposal.pdf";

        Storage::disk(config()->string('proposals.attachment.disk'))
            ->put($path, (string) file_get_contents(database_path('seeders/files/sample-proposal.pdf')));

        $proposal->update([
            'attachment_path' => $path,
            'attachment_original_name' => 'sample-proposal.pdf',
        ]);
    }
}
